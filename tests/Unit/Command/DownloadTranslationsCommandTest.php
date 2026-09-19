<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use Lexio\AdminBundle\Command\Translations\DownloadTranslationsCommand;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DownloadTranslationsCommandTest extends TestCase
{
    public function test_it_exposes_the_pull_command_without_a_provider_argument(): void
    {
        $command = new DownloadTranslationsCommand(
            $this->createStub(TranslationPackageSynchronizerInterface::class),
        );

        self::assertSame('translation:pull', $command->getName());
        self::assertContains('lexio:translations:download', $command->getAliases());
        self::assertCount(0, $command->getDefinition()->getArguments());
    }

    public function test_it_returns_failure_when_the_download_cannot_be_completed(): void
    {
        $synchronizer = $this->createMock(TranslationPackageSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('download')
            ->willThrowException(new TranslationSynchronizationException('The deployed application could not provide the translation package (HTTP 401 Unauthorized).'));
        $tester = new CommandTester(new DownloadTranslationsCommand($synchronizer));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('The translations could not be received.', $tester->getDisplay());
        self::assertStringContainsString(
            'The deployed application could not provide the translation package (HTTP 401 Unauthorized).',
            $tester->getDisplay(),
        );
    }
}
