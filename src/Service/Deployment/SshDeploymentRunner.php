<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

use Lexio\AdminBundle\Contract\Deployment\DeploymentResult;
use Lexio\AdminBundle\Contract\Deployment\DeploymentRunnerInterface;

final readonly class SshDeploymentRunner implements DeploymentRunnerInterface
{
    public function __construct(
        private DeploymentOptions $options,
        private DeploymentConfigurationValidator $validator,
        private SshCommandBuilder $commandBuilder,
        private ProcessRunnerInterface $processRunner,
    ) {
    }

    /**
     * @param callable('out'|'err', string): void $output
     */
    public function deploy(callable $output): DeploymentResult
    {
        $this->validator->validate($this->options);
        $processResult = $this->processRunner->run(
            $this->commandBuilder->build($this->options),
            $output,
            $this->options->timeout,
        );

        return new DeploymentResult(
            $processResult->successful,
            $processResult->exitCode,
            $processResult->timedOut,
        );
    }
}
