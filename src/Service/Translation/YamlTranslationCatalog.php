<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationCatalogInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationEntry;
use Lexio\AdminBundle\Contract\Translation\TranslationCatalogException;
use Lexio\AdminBundle\Contract\Translation\TranslationFile;

final readonly class YamlTranslationCatalog implements TranslationCatalogInterface
{
    public function __construct(
        private string $translationDirectory,
        private bool $enabled,
        private FlatTranslationDocumentCodec $codec,
        private AtomicTranslationFileWriter $writer,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /** @return iterable<int, TranslationFile> */
    public function listFiles(): iterable
    {
        $this->assertEnabled();

        $directory = $this->resolveDirectory();
        $paths = glob($directory . DIRECTORY_SEPARATOR . '*.yaml');
        if (false === $paths) {
            throw new TranslationCatalogException('Unable to inspect the translation directory.');
        }

        $files = [];
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $metadata = $this->codec->parseFilename(basename($path));
            if (null === $metadata) {
                continue;
            }

            try {
                $this->readEntries($path);
            } catch (TranslationCatalogException) {
                continue;
            }

            $files[] = new TranslationFile(
                domain: $metadata['domain'],
                locale: $metadata['locale'],
                filename: basename($path),
            );
        }

        usort(
            $files,
            static fn (TranslationFile $left, TranslationFile $right): int => [$left->domain, $left->locale] <=> [$right->domain, $right->locale],
        );

        return $files;
    }

    /** @return iterable<int, TranslationEntry> */
    public function getEntries(string $domain, string $locale): iterable
    {
        $this->assertEnabled();

        return $this->readEntries($this->resolveFile($domain, $locale));
    }

    public function update(string $domain, string $locale, string $key, string $value): void
    {
        $this->assertEnabled();
        try {
            $this->codec->assertKey($key);
        } catch (InvalidTranslationDocumentException $exception) {
            throw new TranslationCatalogException('The translation key is invalid.', previous: $exception);
        }

        $path = $this->resolveFile($domain, $locale);
        $document = $this->readDocument($path);
        $entries = $document['entries'];
        $updated = false;

        foreach ($entries as $entry) {
            $updated = $updated || $entry->key === $key;
        }

        if (!$updated) {
            throw new TranslationCatalogException('The requested translation key does not exist.');
        }

        try {
            $serializedValue = $this->codec->serializeValue($value);
        } catch (InvalidTranslationDocumentException $exception) {
            throw new TranslationCatalogException('The translation value could not be serialized.', previous: $exception);
        }
        $updatedContent = $this->replaceValue($document['content'], $key, $serializedValue);
        $this->parseContent($updatedContent);

        try {
            $this->writer->write($path, $updatedContent);
        } catch (TranslationStorageException $exception) {
            throw new TranslationCatalogException('The translation file cannot be written.', previous: $exception);
        }
    }

    private function resolveDirectory(): string
    {
        $directory = realpath($this->translationDirectory);
        if (false === $directory || !is_dir($directory) || !is_readable($directory)) {
            throw new TranslationCatalogException('The translation directory is unavailable.');
        }

        return $directory;
    }

    private function resolveFile(string $domain, string $locale): string
    {
        if (1 !== preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/D', $domain)) {
            throw new TranslationCatalogException('The translation domain is invalid.');
        }

        if (1 !== preg_match('/^[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*$/D', $locale)) {
            throw new TranslationCatalogException('The translation locale is invalid.');
        }

        $directory = $this->resolveDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . $domain . '.' . $locale . '.yaml';
        $realPath = realpath($path);

        if (false === $realPath || !is_file($realPath) || !is_readable($realPath)) {
            throw new TranslationCatalogException('The translation file is unavailable.');
        }

        if (strtolower((string) realpath(dirname($realPath))) !== strtolower($directory)) {
            throw new TranslationCatalogException('The translation file is outside the configured directory.');
        }

        return $realPath;
    }

    /** @return list<TranslationEntry> */
    private function readEntries(string $path): array
    {
        return $this->readDocument($path)['entries'];
    }

    /** @return array{content: string, entries: list<TranslationEntry>} */
    private function readDocument(string $path): array
    {
        $content = file_get_contents($path);
        if (false === $content) {
            throw new TranslationCatalogException('The translation file cannot be read.');
        }

        $parsed = $this->parseContent($content);

        $entries = [];
        foreach ($parsed as $key => $value) {
            $entries[] = new TranslationEntry(key: $key, value: $value);
        }

        return ['content' => $content, 'entries' => $entries];
    }

    /** @return array<string, string> */
    private function parseContent(string $content): array
    {
        try {
            return $this->codec->parse($content);
        } catch (InvalidTranslationDocumentException $exception) {
            throw new TranslationCatalogException('The translation file contains invalid YAML.', previous: $exception);
        }
    }

    private function replaceValue(string $content, string $key, string $serializedValue): string
    {
        $pattern = '/^' . preg_quote($key, '/') . ':[^\r\n]*(?:\r\n|\n|\r|$)/m';
        $updatedContent = preg_replace_callback(
            $pattern,
            static function (array $match) use ($key, $serializedValue): string {
                $matchedLine = $match[0] ?? null;
                if (!is_string($matchedLine)) {
                    throw new TranslationCatalogException('The translation key could not be updated.');
                }

                $lineEnding = str_ends_with($matchedLine, "\r\n")
                    ? "\r\n"
                    : (str_ends_with($matchedLine, "\n") ? "\n" : (str_ends_with($matchedLine, "\r") ? "\r" : ''));

                return $key . ': ' . $serializedValue . $lineEnding;
            },
            $content,
            -1,
            $count,
        );

        if (null === $updatedContent || 1 !== $count) {
            throw new TranslationCatalogException('The translation key could not be updated.');
        }

        return $updatedContent;
    }

    private function assertEnabled(): void
    {
        if (!$this->enabled) {
            throw new TranslationCatalogException('Translation management is disabled.');
        }
    }
}
