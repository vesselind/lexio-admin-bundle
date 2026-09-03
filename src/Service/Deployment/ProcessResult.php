<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

final readonly class ProcessResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public bool $timedOut,
    ) {
    }
}
