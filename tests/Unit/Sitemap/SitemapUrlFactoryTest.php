<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Sitemap;

use Lexio\AdminBundle\Sitemap\SitemapUrlFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapUrlFactoryTest extends TestCase
{
    public function test_it_generates_an_absolute_localized_url(): void
    {
        $lastModified = new \DateTimeImmutable('2026-09-08');
        $request = Request::create('/en/sitemap_blogs.xml');
        $request->setLocale('en');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with(
                'blog.show',
                ['slug' => 'bundle-sitemaps', '_locale' => 'en'],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://example.com/en/blog/bundle-sitemaps');

        $url = (new SitemapUrlFactory($urlGenerator, $requestStack))->fromRoute(
            routeName: 'blog.show',
            routeParameters: ['slug' => 'bundle-sitemaps'],
            lastModified: $lastModified,
        );

        self::assertSame('https://example.com/en/blog/bundle-sitemaps', $url->location);
        self::assertSame($lastModified, $url->lastModified);
    }
}
