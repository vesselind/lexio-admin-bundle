<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Sitemap;

final readonly class SitemapUrl
{
    public function __construct(
        public string $location,
        public ?\DateTimeInterface $lastModified = null,
        public ?string $changeFrequency = null,
        public ?float $priority = null,
    ) {
    }
}
