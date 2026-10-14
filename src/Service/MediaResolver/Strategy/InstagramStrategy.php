<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbedStrategyInterface;

/**
 * Resolves Instagram posts, reels and IGTV videos to the official embed iframe.
 */
final readonly class InstagramStrategy implements MediaEmbedStrategyInterface
{
    private const HOSTS = ['instagram.com', 'www.instagram.com', 'm.instagram.com'];

    private const POST_HEIGHT = 710;

    public function getName(): string
    {
        return 'instagram';
    }

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && in_array(strtolower($host), self::HOSTS, true);
    }

    public function createEmbed(string $url): ?MediaEmbed
    {
        $parts = parse_url($url);

        if (!is_array($parts)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');

        if (!preg_match('~^/(p|reel|reels|tv)/([A-Za-z0-9_-]{5,})~', $path, $matches)) {
            return null;
        }

        $type = 'reels' === $matches[1] ? 'reel' : $matches[1];
        $shortcode = $matches[2];

        $src = 'p' === $type
            ? sprintf('https://www.instagram.com/p/%s/embed/captioned/', $shortcode)
            : sprintf('https://www.instagram.com/%s/%s/embed/', $type, $shortcode);

        return new MediaEmbed($src, $this->getName(), height: self::POST_HEIGHT);
    }
}
