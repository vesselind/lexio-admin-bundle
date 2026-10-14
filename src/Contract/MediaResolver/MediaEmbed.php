<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\MediaResolver;

/**
 * Resolved media embed, rendered by the bundle as a responsive iframe.
 *
 * When a fixed height is provided (card-style embeds such as Instagram posts
 * or Facebook posts), it takes precedence over the aspect ratio.
 */
final readonly class MediaEmbed
{
    public function __construct(
        public string $src,
        public string $provider,
        public string $aspectRatio = '16 / 9',
        public ?int $height = null,
    ) {
    }
}
