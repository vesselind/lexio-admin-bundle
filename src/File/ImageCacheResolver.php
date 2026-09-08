<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\File;

use Intervention\Image\Interfaces\ImageInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;

/**
 * Resolves an image entity to a cached WebP file, creating it on demand.
 */
final class ImageCacheResolver
{
    public const string CACHE_SYSTEM_DIR = 'public/media/cache';
    public const string CACHE_WEB_DIR    = '/media/cache';
    public const string CACHE_EXTENSION  = 'webp';

    /** If either dimension of the source exceeds this, scale it down first. */
    private const MAX_SOURCE_DIMENSION = 2000;

    public function __construct(
        private readonly FileManager               $fileManager,
        private readonly InterventionImageManager $interventionImageManager,
        private readonly string                    $projectDir,
    ) {
    }

    /**
     * Returns the web path to a cached (and possibly resized/converted) version of the image.
     *
     * SVG files are returned as-is.
     *
     * @param ImageEntityInterface $image The image entity to cache.
     */
    public function resolveImage(ImageEntityInterface $image, ImageCacheConfig $config): string
    {
        if ($this->getExtension($image->getFilePath()) === 'svg') {
            return $image->getFilePath();
        }

        $cachedImageName = $this->getNameWithoutExtension($image->getName() ?? '')
            . '_' . $config->getIdentifier() . '.' . self::CACHE_EXTENSION;

        $cachedWebPath = self::CACHE_WEB_DIR . '/' . $cachedImageName;

        if ($this->cacheExists($cachedWebPath)) {
            return $cachedWebPath;
        }

        $systemFilePath    = $this->fileManager->getSystemPath($image);
        $interventionImage = $this->interventionImageManager->getInstance()->read($systemFilePath);
        $interventionImage = $this->downscaleIfOversized($interventionImage);
        $interventionImage = $this->applyFilter($interventionImage, $config);

        $cachedImageSystemPath = $this->projectDir . '/' . self::CACHE_SYSTEM_DIR . '/' . $cachedImageName;
        $interventionImage->toWebp($config->getQuality())->save($cachedImageSystemPath);

        return $cachedWebPath;
    }

    public function cacheExists(string $cachedWebPath): bool
    {
        $cachedSystemPath = $this->projectDir . '/' . self::CACHE_SYSTEM_DIR . '/' . basename($cachedWebPath);

        return file_exists($cachedSystemPath);
    }

    private function downscaleIfOversized(ImageInterface $image): ImageInterface
    {
        if ($image->width() > self::MAX_SOURCE_DIMENSION || $image->height() > self::MAX_SOURCE_DIMENSION) {
            return $image->scale(self::MAX_SOURCE_DIMENSION, self::MAX_SOURCE_DIMENSION);
        }

        return $image;
    }

    private function applyFilter(ImageInterface $image, ImageCacheConfig $config): ImageInterface
    {
        return match ($config->getFilter()) {
            ImageCacheConfig::FILTER_COVER => $image->cover((int) $config->width, (int) $config->height),
            ImageCacheConfig::FILTER_SCALE => $image->scale((int) $config->width, (int) $config->height),
            default => throw new \InvalidArgumentException('Unsupported filter type: ' . $config->getFilter()),
        };
    }

    private function getNameWithoutExtension(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    private function getExtension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }
}


