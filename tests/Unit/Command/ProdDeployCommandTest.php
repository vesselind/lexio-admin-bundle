<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use Lexio\AdminBundle\Command\ProdDeployCommand;
use Lexio\AdminBundle\Contract\Deployment\DeploymentResult;
use Lexio\AdminBundle\Contract\Deployment\DeploymentRunnerInterface;
use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationException;
use Lexio\AdminBundle\Service\Deployment\DeploymentTranslationKeys;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProdDeployCommandTest extends TestCase
{
    public function test_streams_remote_output_and_returns_success(): void
    {
        $runner = $this->createMock(DeploymentRunnerInterface::class);
        $runner->expects(self::once())
            ->method('deploy')
            ->willReturnCallback(static function (callable $output): DeploymentResult {
                $output('out', "remote stdout\n");
                $output('err', "remote stderr\n");

                return new DeploymentResult(true, 0, false);
            });

        $tester = new CommandTester(new ProdDeployCommand($runner, $this->translator()));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('remote stdout', $tester->getDisplay());
        self::assertStringContainsString('remote stderr', $tester->getDisplay());
        self::assertStringContainsString(DeploymentTranslationKeys::COMPLETED, $tester->getDisplay());
    }

    public function test_returns_failure_for_configuration_errors(): void
    {
        $runner = $this->createStub(DeploymentRunnerInterface::class);
        $runner->method('deploy')->willThrowException(new DeploymentConfigurationException(
            new TranslatableMessage(DeploymentTranslationKeys::DISABLED, [], 'LexioAdminBundle'),
        ));

        $tester = new CommandTester(new ProdDeployCommand($runner, $this->translator()));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(DeploymentTranslationKeys::DISABLED, $tester->getDisplay());
    }

    public function test_returns_failure_for_non_zero_deployment_result(): void
    {
        $runner = $this->createStub(DeploymentRunnerInterface::class);
        $runner->method('deploy')->willReturn(new DeploymentResult(false, 7, false));

        $tester = new CommandTester(new ProdDeployCommand($runner, $this->translator()));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(DeploymentTranslationKeys::FAILED, $tester->getDisplay());
    }

    private function translator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string => $id,
        );

        return $translator;
    }
}
