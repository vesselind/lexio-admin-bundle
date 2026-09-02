<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Loads and caches admin listing route names from the router.
 */
final readonly class RouteLoader
{
    public const string CACHE_KEY = 'listing_routes';

    public function __construct(private RouterInterface $router)
    {
    }

    /**
     * Returns all admin route names that end with 'index', e.g. admin.blog.index.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException
     */
    public function adminListingRoutes(): array
    {
        $cache = new FilesystemAdapter();

        return $cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            /** @var list<string> $routes */
            $routes = [];

            foreach ($this->router->getRouteCollection() as $route) {
                $path = $route->getPath();
                if (str_starts_with($path, '/admin')) {
                    try {
                        $routeName = $this->router->match($path)['_route'] ?? null;
                        if (is_string($routeName)) {
                            $routes[] = $routeName;
                        }
                    } catch (\Exception) {
                        continue;
                    }
                }
            }

            return array_values(array_filter($routes, static fn (string $r): bool => str_ends_with($r, 'index')));
        });
    }
}

