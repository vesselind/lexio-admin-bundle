<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Service\MediaResolver\Strategy\YouTubeStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class YouTubeStrategyTest extends TestCase
{
    private YouTubeStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new YouTubeStrategy();
    }

    public function test_it_is_named_youtube(): void
    {
        self::assertSame('youtube', $this->strategy->getName());
    }

    #[DataProvider('supportedUrls')]
    public function test_it_supports_youtube_urls(string $url): void
    {
        self::assertTrue($this->strategy->supports($url));
    }

    /** @return iterable<string, array{string}> */
    public static function supportedUrls(): iterable
    {
        yield 'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'share link' => ['https://youtu.be/dQw4w9WgXcQ'];
        yield 'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'music' => ['https://music.youtube.com/watch?v=dQw4w9WgXcQ'];
        yield 'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ'];
        yield 'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ'];
        yield 'live' => ['https://www.youtube.com/live/dQw4w9WgXcQ'];
        yield 'nocookie' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'];
    }

    public function test_it_does_not_support_other_hosts(): void
    {
        self::assertFalse($this->strategy->supports('https://vimeo.com/123456'));
        self::assertFalse($this->strategy->supports('not a url'));
    }

    #[DataProvider('embeddableUrls')]
    public function test_it_builds_embeddable_urls(string $url, string $expectedSrc): void
    {
        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame($expectedSrc, $embed->src);
        self::assertSame('youtube', $embed->provider);
        self::assertSame('16 / 9', $embed->aspectRatio);
    }

    /** @return iterable<string, array{string, string}> */
    public static function embeddableUrls(): iterable
    {
        yield 'watch' => [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ];
        yield 'share link' => [
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ];
        yield 'share link with start time' => [
            'https://youtu.be/dQw4w9WgXcQ?t=90',
            'https://www.youtube.com/embed/dQw4w9WgXcQ?start=90',
        ];
        yield 'watch with start parameter' => [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&start=42',
            'https://www.youtube.com/embed/dQw4w9WgXcQ?start=42',
        ];
        yield 'shorts' => [
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ];
        yield 'live' => [
            'https://www.youtube.com/live/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
        ];
    }

    public function test_it_returns_null_without_a_video_id(): void
    {
        self::assertNull($this->strategy->createEmbed('https://www.youtube.com/watch'));
        self::assertNull($this->strategy->createEmbed('https://youtu.be/'));
        self::assertNull($this->strategy->createEmbed('https://www.youtube.com/watch?v=abc'));
    }

    public function test_it_returns_null_for_malformed_urls(): void
    {
        self::assertNull($this->strategy->createEmbed('not a url'));
    }

    #[DataProvider('timestamps')]
    public function test_it_parses_complete_timestamps(string $timestamp, ?int $seconds): void
    {
        $embed = $this->strategy->createEmbed('https://youtu.be/dQw4w9WgXcQ?t=' . rawurlencode($timestamp));

        self::assertNotNull($embed);
        self::assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ' . (null === $seconds ? '' : '?start=' . $seconds), $embed->src);
    }

    public static function timestamps(): iterable
    {
        yield 'minutes and seconds' => ['1m30s', 90];
        yield 'hours minutes seconds' => ['1h2m3s', 3723];
        yield 'hours and seconds' => ['2h10s', 7210];
        yield 'hours' => ['1h', 3600];
        yield 'minutes' => ['2m', 120];
        yield 'seconds suffix' => ['45s', 45];
        yield 'numeric seconds' => ['90', 90];
        yield 'zero' => ['0h0m0s', null];
        yield 'empty' => ['', null];
        yield 'negative' => ['-30', null];
        yield 'trailing junk' => ['1m30seconds', null];
        yield 'wrong order' => ['30s1m', null];
        yield 'overflow' => ['999999999999999999999999h', null];
    }
}
