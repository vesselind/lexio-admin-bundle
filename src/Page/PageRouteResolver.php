<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Page;

use Lexio\AdminBundle\Attributes\Page as PageAttribute;
use ReflectionMethod;
use Symfony\Component\Routing\RouterInterface;

final readonly class PageRouteResolver
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function findRouteForPage(string $pageClass): ?string
    {
        foreach ($this->router->getRouteCollection() as $routeName => $route) {
            $controller = $route->getDefault('_controller');

            $methods = $route->getMethods();

            if (!is_string($controller) || ($methods !== [] && !in_array('GET', $methods, true))) {
                continue;
            }

            [$controllerClass, $methodName] = str_contains($controller, '::')
                ? explode('::', $controller, 2)
                : [$controller, '__invoke'];

            if (!class_exists($controllerClass) || !method_exists($controllerClass, $methodName)) {
                continue;
            }

            $attributes = (new ReflectionMethod($controllerClass, $methodName))->getAttributes(PageAttribute::class);

            foreach ($attributes as $attribute) {
                if ($attribute->newInstance()->pageClass === $pageClass) {
                    return $routeName;
                }
            }
        }

        return null;
    }
}
