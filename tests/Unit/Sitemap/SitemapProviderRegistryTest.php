<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Sitemap;

use Lexio\AdminBundle\Contract\Sitemap\SitemapProviderInterface;
use Lexio\AdminBundle\Contract\Sitemap\SitemapUrl;
use Lexio\AdminBundle\Sitemap\SitemapProviderRegistry;
use PHPUnit\Framework\TestCase;

final class SitemapProviderRegistryTest extends TestCase
{
    public function test_it_indexes_and_sorts_providers_by_name(): void
    {
        $blogs = $this->provider('blogs');
        $pages = $this->provider('pages');

        $registry = new SitemapProviderRegistry([$pages, $blogs]);

        self::assertSame(['blogs', 'pages'], array_keys($registry->all()));
        self::assertSame($blogs, $registry->get('blogs'));
        self::assertNull($registry->get('missing'));
    }

    public function test_it_rejects_duplicate_provider_names(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A sitemap provider named "pages" is already registered.');

        new SitemapProviderRegistry([
            $this->provider('pages'),
            $this->provider('pages'),
        ]);
    }

    public function test_it_rejects_names_that_cannot_be_used_in_the_collection_route(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid sitemap provider name "invalid name".');

        new SitemapProviderRegistry([$this->provider('invalid name')]);
    }

    private function provider(string $name): SitemapProviderInterface
    {
        return new class($name) implements SitemapProviderInterface {
            public function __construct(private readonly string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getUrls(): iterable
            {
                yield new SitemapUrl('https://example.com/');
            }
        };
    }
}
