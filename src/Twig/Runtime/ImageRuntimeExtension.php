<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Twig\Runtime;

use Lexio\AdminBundle\File\ImageCacheConfig;
use Lexio\AdminBundle\File\ImageCacheResolver;
use Twig\Attribute\AsTwigFilter;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Provides the `|image` Twig filter for resolving/caching images.
 *
 * Usage:
 *   {{ entity.image|image('cover', '400', '300') }}
 *   {{ '/media/uploads/photo.jpg'|image('scale', '800', '600', 90) }}
 */
final class ImageRuntimeExtension implements RuntimeExtensionInterface
{
    public function __construct(private readonly ImageCacheResolver $imageCacheResolver)
    {
    }

    #[AsTwigFilter('image')]
    public function image(
        string $path,
        string $filter,
        string $width,
        string $height,
        ?int   $quality = null,
    ): string {
        return $this->imageCacheResolver->resolveImage(
            $path,
            new ImageCacheConfig($width, $height, $filter, $quality ?? 82)
        );
    }
}



