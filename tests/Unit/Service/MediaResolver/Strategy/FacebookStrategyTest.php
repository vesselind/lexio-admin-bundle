<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\MediaResolver\Strategy;

use Lexio\AdminBundle\Service\MediaResolver\Strategy\FacebookStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FacebookStrategyTest extends TestCase
{
    private FacebookStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new FacebookStrategy();
    }

    public function test_it_is_named_facebook(): void
    {
        self::assertSame('facebook', $this->strategy->getName());
    }

    #[DataProvider('supportedUrls')]
    public function test_it_supports_facebook_urls(string $url): void
    {
        self::assertTrue($this->strategy->supports($url));
    }

    /** @return iterable<string, array{string}> */
    public static function supportedUrls(): iterable
    {
        yield 'post' => ['https://www.facebook.com/TheEconomist/posts/pfbid06oZduPa3KoyEVaz2v3ZJZ'];
        yield 'mobile' => ['https://m.facebook.com/TheEconomist/posts/pfbid06oZduPa3KoyEVaz2v3ZJZ'];
        yield 'plugin post' => ['https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2FTheEconomist%2Fposts%2Fpfbid06oZ'];
        yield 'short link' => ['https://fb.watch/abc123/'];
    }

    public function test_it_does_not_support_other_hosts(): void
    {
        self::assertFalse($this->strategy->supports('https://www.instagram.com/p/BmMZgokAGGQ/'));
    }

    public function test_it_keeps_plugin_post_urls_and_uses_the_card_height(): void
    {
        $url = 'https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2FTheEconomist%2Fposts%2Fpfbid06oZ&show_text=true';

        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame($url, $embed->src);
        self::assertSame('facebook', $embed->provider);
        self::assertSame(700, $embed->height);
    }

    public function test_it_keeps_plugin_video_urls_as_responsive_embeds(): void
    {
        $url = 'https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Fwatch%2F%3Fv%3D123';

        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame($url, $embed->src);
        self::assertSame('16 / 9', $embed->aspectRatio);
        self::assertNull($embed->height);
    }

    #[DataProvider('videoUrls')]
    public function test_it_builds_video_embeds(string $url): void
    {
        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame('https://www.facebook.com/plugins/video.php?href=' . rawurlencode($url), $embed->src);
        self::assertNull($embed->height);
    }

    /** @return iterable<string, array{string}> */
    public static function videoUrls(): iterable
    {
        yield 'watch' => ['https://www.facebook.com/watch/?v=123456789'];
        yield 'legacy video' => ['https://www.facebook.com/video.php?v=123456789'];
        yield 'page video' => ['https://www.facebook.com/TheEconomist/videos/123456789'];
        yield 'reel' => ['https://www.facebook.com/reel/123456789'];
    }

    #[DataProvider('postUrls')]
    public function test_it_builds_post_embeds(string $url): void
    {
        $embed = $this->strategy->createEmbed($url);

        self::assertNotNull($embed);
        self::assertSame('https://www.facebook.com/plugins/post.php?href=' . rawurlencode($url), $embed->src);
        self::assertSame(700, $embed->height);
    }

    /** @return iterable<string, array{string}> */
    public static function postUrls(): iterable
    {
        yield 'page post' => ['https://www.facebook.com/TheEconomist/posts/pfbid06oZduPa3KoyEVaz2v3ZJZ'];
        yield 'permalink' => ['https://www.facebook.com/permalink.php?story_fbid=123&id=456'];
        yield 'photo' => ['https://www.facebook.com/photo.php?fbid=123'];
        yield 'photos path' => ['https://www.facebook.com/TheEconomist/photos/123'];
    }

    public function test_it_returns_null_for_short_links_that_require_a_redirect(): void
    {
        self::assertNull($this->strategy->createEmbed('https://fb.watch/abc123/'));
    }

    public function test_it_returns_null_for_non_embeddable_paths(): void
    {
        self::assertNull($this->strategy->createEmbed('https://www.facebook.com/TheEconomist'));
        self::assertNull($this->strategy->createEmbed('https://www.facebook.com/watch'));
        self::assertNull($this->strategy->createEmbed('https://www.facebook.com/'));
    }

    public function test_it_returns_null_for_malformed_urls(): void
    {
        self::assertNull($this->strategy->createEmbed('not a url'));
    }

    #[DataProvider('unsafeUrls')]
    public function test_it_rejects_unsafe_urls_even_when_called_directly(string $url): void
    {
        self::assertFalse($this->strategy->supports($url));
        self::assertNull($this->strategy->createEmbed($url));
    }

    public static function unsafeUrls(): iterable
    {
        yield 'javascript post' => ['javascript://www.facebook.com/plugins/post.php%0Aalert(1)'];
        yield 'javascript video' => ['javascript://www.facebook.com/plugins/video.php'];
        yield 'data' => ['data://www.facebook.com/plugins/post.php'];
        yield 'protocol relative' => ['//www.facebook.com/plugins/post.php'];
        yield 'other host' => ['https://evil.example/plugins/post.php'];
        yield 'credentials' => ['https://user:pass@www.facebook.com/plugins/post.php'];
        yield 'port' => ['https://www.facebook.com:8080/plugins/post.php'];
    }

    public function test_it_canonicalizes_plugin_urls_to_the_https_www_host(): void
    {
        $embed = $this->strategy->createEmbed('http://m.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2Fpage%2Fposts%2F123&show_text=true#fragment');

        self::assertNotNull($embed);
        self::assertSame('https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2Fpage%2Fposts%2F123&show_text=true', $embed->src);
    }

    public function test_it_rejects_plugin_path_suffixes(): void
    {
        self::assertNull($this->strategy->createEmbed('https://www.facebook.com/plugins/post.php%0Aalert(1)'));
        self::assertNull($this->strategy->createEmbed('https://www.facebook.com/plugins/video.php/extra'));
    }
}
