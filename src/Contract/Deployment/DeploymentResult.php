<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Deployment;

final readonly class DeploymentResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public bool $timedOut,
    ) {
    }
}
