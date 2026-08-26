<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class TranslationCacheClearer
{
    public function __construct(
        private Filesystem $filesystem,
        private string $cacheDirectory,
    ) {
    }

    public function clear(): void
    {
        $cacheDirectory = realpath($this->cacheDirectory);
        if (false === $cacheDirectory || !is_dir($cacheDirectory)) {
            throw new TranslationCacheException('The application cache directory is unavailable.');
        }

        $translationCacheDirectory = $cacheDirectory . DIRECTORY_SEPARATOR . 'translations';
        if (!$this->filesystem->exists($translationCacheDirectory)) {
            return;
        }

        $resolvedTranslationCacheDirectory = realpath($translationCacheDirectory);
        if (
            false === $resolvedTranslationCacheDirectory
            || strtolower(dirname($resolvedTranslationCacheDirectory)) !== strtolower($cacheDirectory)
            || 'translations' !== basename($resolvedTranslationCacheDirectory)
        ) {
            throw new TranslationCacheException('The translation cache directory is unsafe.');
        }

        try {
            $this->filesystem->remove($resolvedTranslationCacheDirectory);
        } catch (IOExceptionInterface $exception) {
            throw new TranslationCacheException('The translation cache could not be cleared.', previous: $exception);
        }
    }
}
