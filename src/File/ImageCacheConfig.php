<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\File;

/**
 * Value object that describes how an image should be cached/transformed.
 */
final class ImageCacheConfig
{
    public const FILTER_COVER         = 'cover';
    public const FILTER_SCALE         = 'scale';
    public const FILTER_RESIZE_CANVAS = 'resize_canvas';

    public function __construct(
        public readonly string $width,
        public readonly string $height,
        private readonly string $filter = self::FILTER_COVER,
        private readonly int    $quality = 82,
    ) {
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    public function getQuality(): int
    {
        return $this->quality;
    }

    /** Returns a unique cache identifier for this configuration. */
    public function getIdentifier(): string
    {
        return $this->filter . '_' . $this->width . 'x' . $this->height . '_q' . $this->quality;
    }
}

