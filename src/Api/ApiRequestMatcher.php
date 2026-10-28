<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

final readonly class ApiRequestMatcher implements RequestMatcherInterface
{
    private string $pathPrefix;

    public function __construct(string $pathPrefix = '/api/v1')
    {
        $this->pathPrefix = rtrim($pathPrefix, '/');
    }

    public function matches(Request $request): bool
    {
        $path = $request->getPathInfo();

        return $path === $this->pathPrefix || str_starts_with($path, $this->pathPrefix . '/');
    }
}
