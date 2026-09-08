<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Sitemap;

use Lexio\AdminBundle\Contract\Sitemap\SitemapUrl;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class SitemapUrlFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
    ) {
    }

    /** @param array<string, mixed> $routeParameters */
    public function fromRoute(
        string $routeName,
        array $routeParameters = [],
        ?\DateTimeInterface $lastModified = null,
    ): SitemapUrl {
        if ($locale = $this->requestStack->getCurrentRequest()?->getLocale()) {
            $routeParameters['_locale'] = $locale;
        }

        return new SitemapUrl(
            location: $this->urlGenerator->generate(
                $routeName,
                $routeParameters,
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
            lastModified: $lastModified,
        );
    }
}
