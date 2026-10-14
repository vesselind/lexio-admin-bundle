<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Twig;

use Lexio\AdminBundle\Service\MediaResolver\MediaEmbedRenderer;
use Lexio\AdminBundle\Service\MediaResolver\CKEditorContentSanitizer;
use Lexio\AdminBundle\Service\MediaResolver\MediaResolver;
use Lexio\AdminBundle\Service\MediaResolver\Strategy\YouTubeStrategy;
use Lexio\AdminBundle\Twig\CKEditorExtension;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Markup;
use Twig\Node\Node;

final class CKEditorExtensionTest extends TestCase
{
    public function test_it_registers_the_ckeditor_content_filter_as_html_safe(): void
    {
        $filters = $this->extension()->getFilters();

        self::assertCount(1, $filters);
        self::assertSame('ckeditor_content', $filters[0]->getName());
        self::assertSame(['html'], $filters[0]->getSafe(new Node()));
    }

    public function test_it_renders_media_embeds_as_safe_markup(): void
    {
        $content = '<figure class="media"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>';

        $rendered = $this->extension()->renderContent($content);

        self::assertInstanceOf(Markup::class, $rendered);
        self::assertStringContainsString('<iframe', (string) $rendered);
        self::assertStringNotContainsString('<oembed', (string) $rendered);
    }

    public function test_it_returns_empty_markup_for_null_content(): void
    {
        self::assertSame('', (string) $this->extension()->renderContent(null));
    }

    #[DataProvider('unsafeContent')]
    public function test_it_sanitizes_untrusted_html_before_marking_it_safe(string $content, string $forbidden): void
    {
        $html = (string) $this->extension()->renderContent($content);

        self::assertStringNotContainsString($forbidden, strtolower($html));
    }

    public static function unsafeContent(): iterable
    {
        yield 'image event handler' => ['<img src=x onerror=alert(1)>', 'onerror'];
        yield 'script' => ['<p>Text</p><script>alert(1)</script>', '<script'];
        yield 'javascript link' => ['<a href="javascript:alert(1)">Link</a>', 'javascript:'];
        yield 'entity encoded scheme' => ['<a href="java&#x73;cript:alert(1)">Link</a>', 'href='];
        yield 'raw iframe' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>', '<iframe'];
        yield 'svg event' => ['<svg onload=alert(1)></svg>', 'onload'];
        yield 'media event' => ['<figure class="media" onclick="alert(1)"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>', 'onclick'];
        yield 'unsafe figure CSS' => ['<figure class="media" style="width:50%;background:url(javascript:alert(1))"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>', 'background'];
        yield 'unsafe width CSS' => ['<figure class="media" style="width:expression(alert(1))"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>', 'expression'];
    }

    public function test_it_preserves_safe_formatting_images_links_and_resized_embeds(): void
    {
        $html = (string) $this->extension()->renderContent(
            '<p><strong>Bold</strong> <em>Italic</em> <a href="/page">Link</a></p>'
            . '<img src="/image.jpg" alt="Photo">'
            . '<figure class="media" style="width: 560px"><oembed url="https://youtu.be/dQw4w9WgXcQ?t=90"></oembed></figure>',
        );

        self::assertStringContainsString('<strong>Bold</strong>', $html);
        self::assertStringContainsString('<em>Italic</em>', $html);
        self::assertStringContainsString('href="/page"', $html);
        self::assertStringContainsString('src="/image.jpg"', $html);
        self::assertStringContainsString('width: 560px', $html);
        self::assertStringContainsString('src="https://www.youtube.com/embed/dQw4w9WgXcQ?start=90"', $html);
    }

    public function test_the_filter_output_is_not_escaped_by_twig(): void
    {
        $twig = new Environment(new ArrayLoader([
            'content.html.twig' => '{{ content|ckeditor_content }}',
        ]));
        $twig->addExtension($this->extension());

        $html = $twig->render('content.html.twig', [
            'content' => '<figure class="media"><oembed url="https://youtu.be/dQw4w9WgXcQ"></oembed></figure>',
        ]);

        self::assertStringContainsString('<iframe', $html);
        self::assertStringNotContainsString('&lt;iframe', $html);
    }

    private function extension(): CKEditorExtension
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturn('Embedded media player');

        return new CKEditorExtension(
            new MediaEmbedRenderer(new MediaResolver([new YouTubeStrategy()]), $translator),
            new CKEditorContentSanitizer(),
        );
    }
}
