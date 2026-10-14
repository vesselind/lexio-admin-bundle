<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbedStrategyInterface;

/**
 * Resolves Facebook posts, photos and videos to the official plugin iframe.
 *
 * Short links (fb.watch) cannot be resolved without following their redirect,
 * so no embed is built for them.
 */
final readonly class FacebookStrategy implements MediaEmbedStrategyInterface
{
    private const HOSTS = [
        'facebook.com',
        'www.facebook.com',
        'm.facebook.com',
        'web.facebook.com',
        'fb.watch',
    ];

    private const POST_HEIGHT = 700;

    public function getName(): string
    {
        return 'facebook';
    }

    public function supports(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && in_array(strtolower((string) ($parts['host'] ?? '')), self::HOSTS, true)
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['port']);
    }

    public function createEmbed(string $url): ?MediaEmbed
    {
        $parts = parse_url($url);

        if (!is_array($parts) || !$this->supports($url)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ('fb.watch' === $host) {
            return null;
        }

        if ($path === '/plugins/post.php') {
            return new MediaEmbed($this->pluginUrl($path, $parts), $this->getName(), height: self::POST_HEIGHT);
        }

        if ($path === '/plugins/video.php') {
            return new MediaEmbed($this->pluginUrl($path, $parts), $this->getName());
        }

        if ($this->isVideo($path, $parts)) {
            return new MediaEmbed(
                'https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url),
                $this->getName(),
            );
        }

        if ($this->isPost($path)) {
            return new MediaEmbed(
                'https://www.facebook.com/plugins/post.php?href=' . rawurlencode($url),
                $this->getName(),
                height: self::POST_HEIGHT,
            );
        }

        return null;
    }

    /** @param array<string, int|string> $parts */
    private function pluginUrl(string $path, array $parts): string
    {
        $query = (string) ($parts['query'] ?? '');

        return 'https://www.facebook.com' . $path . ($query !== '' ? '?' . $query : '');
    }

    /** @param array<string, int|string> $parts */
    private function isVideo(string $path, array $parts): bool
    {
        if (str_contains($path, '/videos/') || str_starts_with($path, '/reel/')) {
            return true;
        }

        if (!str_starts_with($path, '/watch') && !str_starts_with($path, '/video.php')) {
            return false;
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);

        return is_string($query['v'] ?? null) && '' !== $query['v'];
    }

    private function isPost(string $path): bool
    {
        return str_contains($path, '/posts/')
            || str_contains($path, '/photos/')
            || str_starts_with($path, '/permalink.php')
            || str_starts_with($path, '/photo.php');
    }
}
