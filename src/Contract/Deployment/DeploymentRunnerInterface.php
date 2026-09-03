<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Deployment;

interface DeploymentRunnerInterface
{
    /**
     * @param callable('out'|'err', string): void $output
     */
    public function deploy(callable $output): DeploymentResult;
}
