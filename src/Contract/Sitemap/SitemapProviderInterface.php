<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Sitemap;

interface SitemapProviderInterface
{
    public function getName(): string;

    /** @return iterable<SitemapUrl> */
    public function getUrls(): iterable;
}
