<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Deployment;

use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationException;
use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationValidator;
use Lexio\AdminBundle\Service\Deployment\DeploymentOptions;
use Lexio\AdminBundle\Service\Deployment\DeploymentTranslationKeys;
use Lexio\AdminBundle\Service\Deployment\ProcessResult;
use Lexio\AdminBundle\Service\Deployment\ProcessRunnerInterface;
use Lexio\AdminBundle\Service\Deployment\SshCommandBuilder;
use Lexio\AdminBundle\Service\Deployment\SshDeploymentRunner;
use PHPUnit\Framework\TestCase;

final class SshDeploymentRunnerTest extends TestCase
{
    public function test_deploys_and_forwards_process_output(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $output = [];
        $processRunner->expects(self::once())
            ->method('run')
            ->willReturnCallback(static function (array $command, callable $callback, ?int $timeout) use (&$output): ProcessResult {
                self::assertSame('ssh', $command[0]);
                self::assertSame(300, $timeout);
                $callback('out', 'remote output');

                return new ProcessResult(true, 0, false);
            });

        $result = $this->createRunner(
            new DeploymentOptions(
                true,
                'deploy.example.com',
                'deployer',
                22,
                '/srv/app',
                'scripts/deploy.sh',
                null,
                300,
            ),
            $processRunner,
        )->deploy(static function (string $type, string $buffer) use (&$output): void {
            $output[$type] = $buffer;
        });

        self::assertTrue($result->successful);
        self::assertSame(0, $result->exitCode);
        self::assertFalse($result->timedOut);
        self::assertSame(['out' => 'remote output'], $output);
    }

    public function test_returns_unsuccessful_result_for_remote_failure(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::once())
            ->method('run')
            ->willReturn(new ProcessResult(false, 7, false));

        $result = $this->createRunner($this->validOptions(), $processRunner)->deploy(
            static function (string $type, string $buffer): void {
            },
        );

        self::assertFalse($result->successful);
        self::assertSame(7, $result->exitCode);
        self::assertFalse($result->timedOut);
    }

    public function test_returns_unsuccessful_timed_out_result(): void
    {
        $processRunner = $this->createStub(ProcessRunnerInterface::class);
        $processRunner->method('run')->willReturn(new ProcessResult(false, 124, true));

        $result = $this->createRunner($this->validOptions(), $processRunner)->deploy(
            static function (string $type, string $buffer): void {
            },
        );

        self::assertFalse($result->successful);
        self::assertSame(124, $result->exitCode);
        self::assertTrue($result->timedOut);
    }

    public function test_disabled_deployment_never_creates_a_process(): void
    {
        $processRunner = $this->createMock(ProcessRunnerInterface::class);
        $processRunner->expects(self::never())->method('run');

        try {
            $this->createRunner(new DeploymentOptions(
                false,
                null,
                null,
                22,
                null,
                'scripts/dev_next_deploy.sh',
                null,
                null,
            ), $processRunner)->deploy(static function (string $type, string $buffer): void {
            });
            self::fail('Expected disabled deployment to be rejected.');
        } catch (DeploymentConfigurationException $exception) {
            self::assertSame(DeploymentTranslationKeys::DISABLED, $exception->getMessage());
        }
    }

    private function createRunner(DeploymentOptions $options, ProcessRunnerInterface $processRunner): SshDeploymentRunner
    {
        return new SshDeploymentRunner(
            $options,
            new DeploymentConfigurationValidator(),
            new SshCommandBuilder(),
            $processRunner,
        );
    }

    private function validOptions(): DeploymentOptions
    {
        return new DeploymentOptions(
            true,
            'deploy.example.com',
            'deployer',
            22,
            '/srv/app',
            'scripts/deploy.sh',
            null,
            null,
        );
    }
}
