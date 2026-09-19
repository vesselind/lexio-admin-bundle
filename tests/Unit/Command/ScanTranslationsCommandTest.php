<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use Lexio\AdminBundle\Command\Translations\ScanTranslationsCommand;
use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\TranslationScanner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class ScanTranslationsCommandTest extends TestCase
{
    private string $projectDirectory;
    private string $translationDirectory;

    protected function setUp(): void
    {
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-translation-command-' . bin2hex(random_bytes(8));
        $this->translationDirectory = $this->projectDirectory . DIRECTORY_SEPARATOR . 'translations';

        self::assertTrue(mkdir($this->projectDirectory . DIRECTORY_SEPARATOR . 'templates', 0777, true));
        self::assertTrue(mkdir($this->translationDirectory, 0777, true));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function test_it_exposes_the_translation_scan_command_and_its_ci_option(): void
    {
        $command = new ScanTranslationsCommand($this->scanner());

        self::assertSame('translations:scan', $command->getName());
        self::assertTrue($command->getDefinition()->hasArgument('path'));
        self::assertTrue($command->getDefinition()->hasOption('format'));
        self::assertTrue($command->getDefinition()->hasOption('fail-on-findings'));
        self::assertTrue($command->getDefinition()->hasOption('min-length'));
        self::assertTrue($command->getDefinition()->hasOption('exclude'));
    }

    public function test_it_renders_machine_readable_findings(): void
    {
        $this->write('templates/checkout.html.twig', '<p>Checkout</p>');
        $this->write('translations/admin.en.yaml', "admin.title: __admin.title\n");
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::SUCCESS, $tester->execute(['--format' => 'json']));

        $findings = json_decode($tester->getDisplay(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame([
            [
                'file' => 'templates/checkout.html.twig',
                'line' => 1,
                'type' => 'text',
                'value' => 'Checkout',
            ],
            [
                'file' => 'translations/admin.en.yaml',
                'line' => 1,
                'type' => 'placeholder',
                'key' => 'admin.title',
                'value' => '__admin.title',
            ],
        ], $findings);
    }

    public function test_it_returns_failure_for_findings_only_when_requested(): void
    {
        $this->write('templates/checkout.html.twig', '<p>Checkout</p>');
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::FAILURE, $tester->execute(['--fail-on-findings' => true]));
        self::assertStringContainsString('Found 1 potentially untranslated string', $tester->getDisplay());
    }

    public function test_it_rejects_an_unknown_output_format(): void
    {
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::INVALID, $tester->execute(['--format' => 'xml']));
        self::assertStringContainsString('must be either "text" or "json"', $tester->getDisplay());
    }

    public function test_it_rejects_an_invalid_minimum_length(): void
    {
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::INVALID, $tester->execute(['--min-length' => '0']));
        self::assertStringContainsString('--min-length option', $tester->getDisplay());
    }

    public function test_it_rejects_a_missing_explicit_path(): void
    {
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::INVALID, $tester->execute(['path' => 'templates/missing.twig']));
        self::assertStringContainsString('specified path does not exist', $tester->getDisplay());
    }

    public function test_it_reports_invalid_translation_documents_as_failures(): void
    {
        $this->write('translations/admin.en.yaml', "admin:\n  title: __admin.title\n");
        $tester = new CommandTester(new ScanTranslationsCommand($this->scanner()));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString('translations/admin.en.yaml', $tester->getDisplay());
    }

    private function scanner(): TranslationScanner
    {
        return new TranslationScanner(
            projectDirectory: $this->projectDirectory,
            translationDirectory: $this->translationDirectory,
            enabled: true,
            codec: new FlatTranslationDocumentCodec(),
        );
    }

    private function write(string $relativePath, string $content): void
    {
        $path = $this->projectDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true));
        }

        self::assertNotFalse(file_put_contents($path, $content));
    }
}
