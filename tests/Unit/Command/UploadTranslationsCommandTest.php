<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use Lexio\AdminBundle\Command\Translations\UploadTranslationsCommand;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class UploadTranslationsCommandTest extends TestCase
{
    public function test_it_exposes_the_push_command_without_a_provider_argument(): void
    {
        $command = new UploadTranslationsCommand(
            $this->createStub(TranslationPackageSynchronizerInterface::class),
        );

        self::assertSame('translation:push', $command->getName());
        self::assertContains('lexio:translations:upload', $command->getAliases());
        self::assertCount(0, $command->getDefinition()->getArguments());
    }

    public function test_it_returns_success_after_uploading_the_package(): void
    {
        $synchronizer = $this->createMock(TranslationPackageSynchronizerInterface::class);
        $synchronizer->expects(self::once())->method('upload')->willReturn(new TranslationPackageMergeResult(1, 2, 3, 4, 5));
        $tester = new CommandTester(new UploadTranslationsCommand($synchronizer));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Translations sent.', $tester->getDisplay());
        self::assertStringContainsString('Files created: 1', $tester->getDisplay());
    }

    public function test_it_reports_the_synchronization_reason_when_upload_fails(): void
    {
        $synchronizer = $this->createMock(TranslationPackageSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('upload')
            ->willThrowException(new TranslationSynchronizationException('The deployed application rejected the translation package (HTTP 401 Unauthorized).'));
        $tester = new CommandTester(new UploadTranslationsCommand($synchronizer));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'The deployed application rejected the translation package (HTTP 401 Unauthorized).',
            $tester->getDisplay(),
        );
    }
}
