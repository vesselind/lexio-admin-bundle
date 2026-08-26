<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use Lexio\AdminBundle\Command\UploadTranslationsCommand;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class UploadTranslationsCommandTest extends TestCase
{
    public function test_it_returns_success_after_uploading_the_package(): void
    {
        $synchronizer = $this->createMock(TranslationPackageSynchronizerInterface::class);
        $synchronizer->expects(self::once())->method('upload')->willReturn(new TranslationPackageMergeResult(1, 2, 3, 4, 5));
        $tester = new CommandTester(new UploadTranslationsCommand($synchronizer));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Translations sent.', $tester->getDisplay());
        self::assertStringContainsString('Files created: 1', $tester->getDisplay());
    }
}
