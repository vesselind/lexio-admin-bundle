<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Controller;

use Lexio\AdminBundle\Contract\Sitemap\SitemapProviderInterface;
use Lexio\AdminBundle\Contract\Sitemap\SitemapUrl;
use Lexio\AdminBundle\Controller\SitemapController;
use Lexio\AdminBundle\Sitemap\SitemapProviderRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class SitemapControllerTest extends TestCase
{
    public function test_index_renders_absolute_collection_urls_as_xml(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters, int $referenceType): string {
                self::assertSame('sitemap.collection', $route);
                self::assertSame(UrlGeneratorInterface::ABSOLUTE_URL, $referenceType);

                return sprintf('https://example.com/sitemap_%s.xml', $parameters['name']);
            });

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('@LexioAdmin/sitemap/index.xml.twig', [
                'sitemaps' => [
                    'https://example.com/sitemap_blogs.xml',
                    'https://example.com/sitemap_pages.xml',
                ],
            ])
            ->willReturn('<sitemapindex/>');

        $controller = new SitemapController(
            new SitemapProviderRegistry([
                $this->provider('pages'),
                $this->provider('blogs'),
            ]),
            $urlGenerator,
            $twig,
            true,
        );

        $response = $controller->index();

        self::assertSame('<sitemapindex/>', $response->getContent());
        self::assertSame('application/xml; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function test_collection_renders_provider_urls_as_xml(): void
    {
        $url = new SitemapUrl('https://example.com/blog/example');
        $provider = $this->provider('blogs', [$url]);
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(static function (string $template, array $context) use ($url): string {
                self::assertSame('@LexioAdmin/sitemap/collection.xml.twig', $template);
                self::assertArrayHasKey('urls', $context);
                self::assertIsIterable($context['urls']);
                self::assertSame([$url], iterator_to_array($context['urls']));

                return '<urlset/>';
            });

        $controller = new SitemapController(
            new SitemapProviderRegistry([$provider]),
            $this->createStub(UrlGeneratorInterface::class),
            $twig,
            true,
        );

        self::assertSame('<urlset/>', $controller->collection('blogs')->getContent());
    }

    public function test_collection_returns_not_found_for_an_unknown_provider(): void
    {
        $controller = new SitemapController(
            new SitemapProviderRegistry([]),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Environment::class),
            true,
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->collection('missing');
    }

    public function test_disabled_sitemap_returns_not_found(): void
    {
        $controller = new SitemapController(
            new SitemapProviderRegistry([]),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Environment::class),
            false,
        );

        $this->expectException(NotFoundHttpException::class);

        $controller->index();
    }

    /** @param list<SitemapUrl> $urls */
    private function provider(string $name, array $urls = []): SitemapProviderInterface
    {
        return new class($name, $urls) implements SitemapProviderInterface {
            /** @param list<SitemapUrl> $urls */
            public function __construct(
                private readonly string $name,
                private readonly array $urls,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getUrls(): iterable
            {
                yield from $this->urls;
            }
        };
    }
}
