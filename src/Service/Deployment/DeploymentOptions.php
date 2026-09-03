<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

final readonly class DeploymentOptions
{
    public function __construct(
        public bool $enabled,
        public ?string $host,
        public ?string $user,
        public int $port,
        public ?string $remotePath,
        public string $deployScript,
        public ?string $identityFile,
        public ?int $timeout,
    ) {
    }
}
