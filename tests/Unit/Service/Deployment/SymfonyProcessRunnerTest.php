<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Deployment;

use Lexio\AdminBundle\Service\Deployment\SymfonyProcessRunner;
use PHPUnit\Framework\TestCase;

final class SymfonyProcessRunnerTest extends TestCase
{
    public function test_forwards_stdout_and_stderr_and_returns_success(): void
    {
        $output = [];
        $result = (new SymfonyProcessRunner())->run(
            [PHP_BINARY, '-r', 'fwrite(STDOUT, "stdout"); fwrite(STDERR, "stderr");'],
            static function (string $type, string $buffer) use (&$output): void {
                $output[$type] = ($output[$type] ?? '') . $buffer;
            },
            null,
        );

        self::assertTrue($result->successful);
        self::assertSame(0, $result->exitCode);
        self::assertFalse($result->timedOut);
        self::assertSame('stdout', $output['out']);
        self::assertSame('stderr', $output['err']);
    }

    public function test_returns_remote_non_zero_exit_code(): void
    {
        $result = (new SymfonyProcessRunner())->run(
            [PHP_BINARY, '-r', 'exit(7);'],
            static function (string $type, string $buffer): void {
            },
            null,
        );

        self::assertFalse($result->successful);
        self::assertSame(7, $result->exitCode);
        self::assertFalse($result->timedOut);
    }

    public function test_returns_normalized_timeout_result(): void
    {
        $result = (new SymfonyProcessRunner())->run(
            [PHP_BINARY, '-r', 'sleep(2);'],
            static function (string $type, string $buffer): void {
            },
            1,
        );

        self::assertFalse($result->successful);
        self::assertSame(124, $result->exitCode);
        self::assertTrue($result->timedOut);
    }

}
