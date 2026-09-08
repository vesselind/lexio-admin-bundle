<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Sitemap;

use Lexio\AdminBundle\Contract\Sitemap\SitemapProviderInterface;

final readonly class SitemapProviderRegistry
{
    /** @var array<string, SitemapProviderInterface> */
    private array $providers;

    /** @param iterable<SitemapProviderInterface> $providers */
    public function __construct(iterable $providers)
    {
        $registeredProviders = [];

        foreach ($providers as $provider) {
            $name = $provider->getName();

            if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
                throw new \LogicException(sprintf('Invalid sitemap provider name "%s".', $name));
            }

            if (isset($registeredProviders[$name])) {
                throw new \LogicException(sprintf('A sitemap provider named "%s" is already registered.', $name));
            }

            $registeredProviders[$name] = $provider;
        }

        ksort($registeredProviders);
        $this->providers = $registeredProviders;
    }

    /** @return array<string, SitemapProviderInterface> */
    public function all(): array
    {
        return $this->providers;
    }

    public function get(string $name): ?SitemapProviderInterface
    {
        return $this->providers[$name] ?? null;
    }
}
