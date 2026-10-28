<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Api;

use Lexio\AdminBundle\Api\ApiRequestMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRequestMatcherTest extends TestCase
{
    #[DataProvider('paths')]
    public function test_path_boundaries_and_custom_prefixes(string $prefix, string $path, bool $expected): void
    {
        self::assertSame($expected, (new ApiRequestMatcher($prefix))->matches(Request::create($path)));
    }

    public static function paths(): iterable
    {
        yield ['/api/v1', '/api/v1', true];
        yield ['/api/v1', '/api/v1/images/42/show', true];
        yield ['/api/v1', '/api/v10/images', false];
        yield ['/api/v1', '/api/v1-extra', false];
        yield ['/api/v1', '/api/translations/download', false];
        yield ['/bridge/v2/', '/bridge/v2/files/list', true];
        yield ['/bridge/v2/', '/api/v1/files/list', false];
    }
}
