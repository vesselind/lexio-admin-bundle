<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\MediaResolver;

use Lexio\AdminBundle\Service\MediaResolver\MediaEmbedRenderer;
use Lexio\AdminBundle\Service\MediaResolver\MediaResolver;
use Lexio\AdminBundle\Service\MediaResolver\Strategy\FacebookStrategy;
use Lexio\AdminBundle\Service\MediaResolver\Strategy\InstagramStrategy;
use Lexio\AdminBundle\Service\MediaResolver\Strategy\YouTubeStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MediaEmbedRendererTest extends TestCase
{
    public function test_it_replaces_media_figures_with_responsive_iframes(): void
    {
        $content = '<figure class="media"><oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringNotContainsString('<oembed', $rendered);
        self::assertStringContainsString('<figure class="media">', $rendered);
        self::assertStringContainsString('lexio-media-embed--youtube', $rendered);
        self::assertStringContainsString('src="https://www.youtube.com/embed/dQw4w9WgXcQ"', $rendered);
        self::assertStringContainsString('allowfullscreen', $rendered);
        self::assertStringContainsString('aspect-ratio: 16 / 9', $rendered);
    }

    public function test_it_preserves_figure_attributes_including_author_defined_width(): void
    {
        $content = '<figure class="media" style="width: 560px"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('<figure class="media" style="width: 560px">', $rendered);
    }

    public function test_it_decodes_html_entities_in_stored_urls(): void
    {
        $content = '<figure class="media"><oembed url="https://www.youtube.com/watch?v=dQw4w9WgXcQ&amp;t=90"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('src="https://www.youtube.com/embed/dQw4w9WgXcQ?start=90"', $rendered);
    }

    public function test_it_keeps_unsupported_media_embeds_untouched(): void
    {
        $content = '<figure class="media"><oembed url="https://twitter.com/user/status/1"></oembed></figure>';

        self::assertSame($content, $this->renderer()->render($content));
    }

    public function test_it_keeps_non_media_figures_untouched(): void
    {
        $content = '<figure class="image"><img src="/image.jpg" alt=""></figure>';

        self::assertSame($content, $this->renderer()->render($content));
    }

    public function test_it_replaces_loose_oembed_elements_outside_figures(): void
    {
        $content = '<p>Watch this</p><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('<p>Watch this</p>', $rendered);
        self::assertStringContainsString('<figure class="media">', $rendered);
        self::assertStringContainsString('lexio-media-embed--youtube', $rendered);
    }

    public function test_it_replaces_multiple_embeds_in_one_document(): void
    {
        $content = '<figure class="media"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>'
            . '<p>Between</p>'
            . '<figure class="media"><oembed url="https://www.instagram.com/p/BmMZgokAGGQ/"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('lexio-media-embed--youtube', $rendered);
        self::assertStringContainsString('lexio-media-embed--instagram', $rendered);
        self::assertStringContainsString('<p>Between</p>', $rendered);
        self::assertStringNotContainsString('<oembed', $rendered);
    }

    public function test_it_renders_card_embeds_with_a_fixed_height_instead_of_an_aspect_ratio(): void
    {
        $content = '<figure class="media"><oembed url="https://www.instagram.com/p/BmMZgokAGGQ/?taken-by=nasa"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('lexio-media-embed--instagram', $rendered);
        self::assertStringContainsString('src="https://www.instagram.com/p/BmMZgokAGGQ/embed/captioned/"', $rendered);
        self::assertStringContainsString('height: 710px', $rendered);
        self::assertStringNotContainsString('aspect-ratio', $rendered);
    }

    public function test_it_renders_facebook_plugin_embeds_with_a_fixed_height(): void
    {
        $content = '<figure class="media"><oembed url="https://www.facebook.com/plugins/post.php?href=https%3A%2F%2Fwww.facebook.com%2FTheEconomist%2Fposts%2F123&amp;show_text=true"></oembed></figure>';

        $rendered = $this->renderer()->render($content);

        self::assertStringContainsString('lexio-media-embed--facebook', $rendered);
        self::assertStringContainsString('show_text=true', $rendered);
        self::assertStringContainsString('height: 700px', $rendered);
        self::assertStringNotContainsString('aspect-ratio', $rendered);
    }

    public function test_it_returns_content_unchanged_when_no_media_is_present(): void
    {
        $content = '<p>Plain <strong>content</strong>.</p>';

        self::assertSame($content, $this->renderer()->render($content));
    }

    public function test_it_returns_an_empty_string_for_empty_content(): void
    {
        self::assertSame('', $this->renderer()->render(null));
        self::assertSame('', $this->renderer()->render(''));
    }

    private function renderer(): MediaEmbedRenderer
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Embedded media player');

        return new MediaEmbedRenderer(
            new MediaResolver([
                new YouTubeStrategy(),
                new InstagramStrategy(),
                new FacebookStrategy(),
            ]),
            $translator,
        );
    }
}
