<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

interface ProcessRunnerInterface
{
    /**
     * @param list<string> $command
     * @param callable('out'|'err', string): void $output
     */
    public function run(array $command, callable $output, ?int $timeout): ProcessResult;
}
