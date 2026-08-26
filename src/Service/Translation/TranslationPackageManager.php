<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;

final readonly class TranslationPackageManager
{
    public function __construct(
        private string $translationDirectory,
        private TranslationSynchronizationOptions $options,
        private FlatTranslationDocumentCodec $codec,
        private AtomicTranslationFileWriter $writer,
    ) {
    }

    public function export(): string
    {
        $directory = $this->resolveDirectory();
        $paths = glob($directory . DIRECTORY_SEPARATOR . '*.yaml');
        if (false === $paths) {
            throw new TranslationSynchronizationException('The translation package could not be prepared.');
        }

        $documents = [];
        $uncompressedBytes = 0;
        foreach ($paths as $path) {
            $filename = basename($path);
            if (!is_file($path) || is_link($path) || null === $this->codec->parseFilename($filename)) {
                continue;
            }

            $content = file_get_contents($path);
            if (false === $content) {
                throw new TranslationSynchronizationException('The translation package could not be prepared.');
            }

            try {
                $this->codec->parse($content);
            } catch (InvalidTranslationDocumentException $exception) {
                throw new TranslationSynchronizationException('The local translation package contains an invalid document.', previous: $exception);
            }

            $uncompressedBytes += strlen($content);
            if ($uncompressedBytes > $this->options->getMaxPackageBytes()) {
                throw new TranslationSynchronizationException('The uncompressed translation package is too large.');
            }

            $documents[$filename] = $content;
        }

        if (count($documents) > $this->options->getMaxFiles()) {
            throw new TranslationSynchronizationException('The translation package contains too many files.');
        }

        ksort($documents);

        return $this->createArchive($documents);
    }

    public function import(string $archive): TranslationPackageMergeResult
    {
        if (strlen($archive) > $this->options->getMaxPackageBytes()) {
            throw new TranslationSynchronizationException('The translation package is too large.');
        }

        $incomingDocuments = $this->readArchive($archive);
        $directory = $this->resolveDirectory();
        $writes = [];
        $filesCreated = 0;
        $filesUpdated = 0;
        $keysInserted = 0;
        $keysUpdated = 0;
        $keysUnchanged = 0;

        foreach ($incomingDocuments as $filename => $incomingEntries) {
            $path = $directory . DIRECTORY_SEPARATOR . $filename;
            if (is_link($path)) {
                throw new TranslationSynchronizationException('A local translation file is unsafe.');
            }
            $exists = is_file($path);
            $localEntries = [];

            if ($exists) {
                $content = file_get_contents($path);
                if (false === $content) {
                    throw new TranslationSynchronizationException('A local translation file could not be read.');
                }

                try {
                    $localEntries = $this->codec->parse($content);
                } catch (InvalidTranslationDocumentException $exception) {
                    throw new TranslationSynchronizationException('A local translation document is invalid.', previous: $exception);
                }
            }

            $mergedEntries = $localEntries;
            $changed = !$exists;
            foreach ($incomingEntries as $key => $value) {
                if (!array_key_exists($key, $localEntries)) {
                    ++$keysInserted;
                    $changed = true;
                } elseif ($localEntries[$key] !== $value) {
                    ++$keysUpdated;
                    $changed = true;
                } else {
                    ++$keysUnchanged;
                }

                $mergedEntries[$key] = $value;
            }

            if (!$changed) {
                continue;
            }

            try {
                $writes[$path] = $this->codec->dump($mergedEntries);
            } catch (InvalidTranslationDocumentException $exception) {
                throw new TranslationSynchronizationException('The merged translation package is invalid.', previous: $exception);
            }

            if ($exists) {
                ++$filesUpdated;
            } else {
                ++$filesCreated;
            }
        }

        try {
            foreach ($writes as $path => $content) {
                $this->writer->write($path, $content);
            }
        } catch (TranslationStorageException $exception) {
            throw new TranslationSynchronizationException('The translation package could not be stored.', previous: $exception);
        }

        return new TranslationPackageMergeResult(
            filesCreated: $filesCreated,
            filesUpdated: $filesUpdated,
            keysInserted: $keysInserted,
            keysUpdated: $keysUpdated,
            keysUnchanged: $keysUnchanged,
        );
    }

    /** @param array<string, string> $documents */
    private function createArchive(array $documents): string
    {
        if ([] === $documents) {
            return "PK\x05\x06" . str_repeat("\x00", 18);
        }

        $path = tempnam(sys_get_temp_dir(), 'lexio-translation-package-');
        if (false === $path) {
            throw new TranslationSynchronizationException('The translation package could not be prepared.');
        }

        $zip = new \ZipArchive();
        $opened = false;
        try {
            if (true !== $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
                throw new TranslationSynchronizationException('The translation package could not be prepared.');
            }
            $opened = true;

            foreach ($documents as $filename => $content) {
                if (!$zip->addFromString($filename, $content)) {
                    throw new TranslationSynchronizationException('The translation package could not be prepared.');
                }
            }

            if (!$zip->close()) {
                throw new TranslationSynchronizationException('The translation package could not be prepared.');
            }
            $opened = false;

            $archive = file_get_contents($path);
            if (false === $archive || strlen($archive) > $this->options->getMaxPackageBytes()) {
                throw new TranslationSynchronizationException('The translation package is too large or could not be read.');
            }

            return $archive;
        } finally {
            if ($opened) {
                $zip->close();
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @return array<string, array<string, string>> */
    private function readArchive(string $archive): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lexio-translation-package-');
        if (false === $path || false === file_put_contents($path, $archive, LOCK_EX)) {
            throw new TranslationSynchronizationException('The translation package could not be inspected.');
        }

        $zip = new \ZipArchive();
        $opened = false;
        try {
            if (true !== $zip->open($path, \ZipArchive::RDONLY)) {
                throw new TranslationSynchronizationException('The translation package is not a valid ZIP archive.');
            }
            $opened = true;

            if ($zip->numFiles > $this->options->getMaxFiles()) {
                throw new TranslationSynchronizationException('The translation package contains too many files.');
            }

            $documents = [];
            $seenNames = [];
            $uncompressedBytes = 0;
            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $stat = $zip->statIndex($index);
                $filename = $zip->getNameIndex($index);
                if (false === $stat || false === $filename || basename($filename) !== $filename || null === $this->codec->parseFilename($filename)) {
                    throw new TranslationSynchronizationException('The translation package contains an invalid filename.');
                }

                $normalizedName = strtolower($filename);
                if (isset($seenNames[$normalizedName])) {
                    throw new TranslationSynchronizationException('The translation package contains duplicate files.');
                }
                $seenNames[$normalizedName] = true;

                $size = $stat['size'] ?? null;
                if (!is_int($size) || $size < 0) {
                    throw new TranslationSynchronizationException('The translation package contains an invalid file.');
                }
                $uncompressedBytes += $size;
                if ($uncompressedBytes > $this->options->getMaxPackageBytes()) {
                    throw new TranslationSynchronizationException('The uncompressed translation package is too large.');
                }

                $content = $zip->getFromIndex($index);
                if (false === $content || strlen($content) !== $size) {
                    throw new TranslationSynchronizationException('A translation package file could not be read.');
                }

                try {
                    $documents[$filename] = $this->codec->parse($content);
                } catch (InvalidTranslationDocumentException $exception) {
                    throw new TranslationSynchronizationException('The translation package contains an invalid document.', previous: $exception);
                }
            }

            return $documents;
        } finally {
            if ($opened) {
                $zip->close();
            }
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function resolveDirectory(): string
    {
        $directory = realpath($this->translationDirectory);
        if (false === $directory || !is_dir($directory) || !is_readable($directory) || !is_writable($directory)) {
            throw new TranslationSynchronizationException('The translation directory is unavailable.');
        }

        return $directory;
    }
}
