<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

final readonly class AtomicTranslationFileWriter
{
    public function write(string $path, string $content): void
    {
        $temporaryPath = tempnam(dirname($path), '.lexio-translation-');
        if (false === $temporaryPath) {
            throw new TranslationStorageException('The translation file cannot be prepared for writing.');
        }

        try {
            $bytesWritten = file_put_contents($temporaryPath, $content, LOCK_EX);
            if (false === $bytesWritten || $bytesWritten !== strlen($content)) {
                throw new TranslationStorageException('The translation file cannot be written.');
            }

            if (is_file($path)) {
                $permissions = fileperms($path);
                if (false !== $permissions && !chmod($temporaryPath, $permissions & 0777)) {
                    throw new TranslationStorageException('The translation file permissions cannot be preserved.');
                }
            }

            if (!rename($temporaryPath, $path)) {
                throw new TranslationStorageException('The translation file cannot be replaced.');
            }

            $temporaryPath = null;
        } finally {
            if (null !== $temporaryPath && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
