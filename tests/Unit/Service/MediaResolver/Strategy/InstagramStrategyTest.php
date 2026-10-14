<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Service\MediaResolver\Strategy\InstagramStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InstagramStrategyTest extends TestCase
{
    private InstagramStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new InstagramStrategy();
    }

    public function test_it_is_named_instagram(): void
    {
        self::assertSame('instagram', $this->strategy->getName());
    }

    #[DataProvider('supportedUrls')]
    public function test_it_supports_instagram_urls(string $url): void
    {
        self::assertTrue($this->strategy->supports($url));
    }

    /** @return iterable<string, array{string}> */
    public static function supportedUrls(): iterable
    {
        yield 'post' => ['https://www.instagram.com/p/BmMZgokAGGQ/'];
        yield 'reel' => ['https://www.instagram.com/reel/CxYz123abc/'];
        yield 'mobile' => ['https://m.instagram.com/p/BmMZgokAGGQ/'];
        yield 'embed' => ['https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/'];
    }

    public function test_it_does_not_support_other_hosts(): void
    {
        self::assertFalse($this->strategy->supports('https://www.facebook.com/TheEconomist/posts/123'));
    }

    #[DataProvider('embeddableUrls')]
    public function test_it_builds_embeddable_urls(string $url, string $expectedSrc): void
    {
        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame($expectedSrc, $embed->src);
        self::assertSame('instagram', $embed->provider);
        self::assertSame(710, $embed->height);
    }

    /** @return iterable<string, array{string, string}> */
    public static function embeddableUrls(): iterable
    {
        yield 'post' => [
            'https://www.instagram.com/p/BmMZgokAGGQ/',
            'https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/',
        ];
        yield 'post with query parameters' => [
            'https://www.instagram.com/p/BmMZgokAGGQ/?taken-by=nasa',
            'https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/',
        ];
        yield 'reel' => [
            'https://www.instagram.com/reel/CxYz123abc/',
            'https://www.instagram.com/reel/CxYz123abc/embed/',
        ];
        yield 'plural reels path' => [
            'https://www.instagram.com/reels/CxYz123abc/',
            'https://www.instagram.com/reel/CxYz123abc/embed/',
        ];
        yield 'igtv' => [
            'https://www.instagram.com/tv/AbCdEf123/',
            'https://www.instagram.com/tv/AbCdEf123/embed/',
        ];
        yield 'already an embed url' => [
            'https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/',
            'https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/',
        ];
    }

    public function test_it_returns_null_for_non_media_paths(): void
    {
        self::assertNull($this->strategy->createEmbed('https://www.instagram.com/nasa/'));
        self::assertNull($this->strategy->createEmbed('https://www.instagram.com/'));
        self::assertNull($this->strategy->createEmbed('https://www.instagram.com/p/'));
    }

    public function test_it_returns_null_for_malformed_urls(): void
    {
        self::assertNull($this->strategy->createEmbed('not a url'));
    }
}
