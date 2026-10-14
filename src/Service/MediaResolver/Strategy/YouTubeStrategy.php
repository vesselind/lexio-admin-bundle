<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbedStrategyInterface;

/**
 * Resolves YouTube videos (watch, share, embed, shorts and live URLs) to a playable embed.
 */
final readonly class YouTubeStrategy implements MediaEmbedStrategyInterface
{
    private const HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtu.be',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
    ];

    public function getName(): string
    {
        return 'youtube';
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

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);

        $videoId = $this->extractVideoId($host, $path, $query);

        if (null === $videoId) {
            return null;
        }

        $src = 'https://www.youtube.com/embed/' . $videoId;
        $start = $this->extractStartTime($query);

        if (null !== $start) {
            $src .= '?start=' . $start;
        }

        return new MediaEmbed($src, $this->getName());
    }

    /** @param array<int|string, array<mixed>|string> $query */
    private function extractVideoId(string $host, string $path, array $query): ?string
    {
        $candidates = [];

        if ('youtu.be' === $host) {
            $candidates[] = trim($path, '/');
        }

        $queryVideoId = $query['v'] ?? null;

        if (is_string($queryVideoId)) {
            $candidates[] = $queryVideoId;
        }

        if (preg_match('~^/(?:v|embed|shorts|live)/([^/?#]+)~', $path, $matches)) {
            $candidates[] = $matches[1];
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/^[A-Za-z0-9_-]{6,}$/', $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param array<int|string, array<mixed>|string> $query */
    private function extractStartTime(array $query): ?int
    {
        $start = $query['start'] ?? $query['t'] ?? null;

        if (!is_string($start)) {
            return null;
        }

        if (preg_match('/^\d+$/D', $start)) {
            $components = [[$start, 1]];
        } elseif ($start !== '' && preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/D', $start, $matches)) {
            $components = [[$matches[1] ?? '0', 3600], [$matches[2] ?? '0', 60], [$matches[3] ?? '0', 1]];
        } else {
            return null;
        }

        $seconds = 0;
        foreach ($components as [$value, $unit]) {
            $value = ltrim($value, '0');
            $maximum = (string) intdiv(PHP_INT_MAX - $seconds, $unit);

            if (strlen($value) > strlen($maximum) || (strlen($value) === strlen($maximum) && strcmp($value, $maximum) > 0)) {
                return null;
            }

            $seconds += (int) $value * $unit;
        }

        return $seconds > 0 ? $seconds : null;
    }
}
