<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Lexio\AdminBundle\Service\Translation\AtomicTranslationFileWriter;
use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\TranslationPackageManager;
use Lexio\AdminBundle\Service\Translation\TranslationSynchronizationOptions;
use PHPUnit\Framework\TestCase;

final class TranslationPackageManagerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-package-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0777, true));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($this->directory);
    }

    public function test_it_exports_supported_translation_files(): void
    {
        $this->write('admin.en.yaml', "label.title: Title\n");
        $this->write('ignored.json', '{}');

        $archive = $this->manager()->export();
        $files = $this->archiveFiles($archive);

        self::assertSame(['admin.en.yaml' => "label.title: Title\n"], $files);
    }

    public function test_an_empty_translation_directory_produces_a_valid_no_op_package(): void
    {
        $archive = $this->manager()->export();
        $result = $this->manager()->import($archive);

        self::assertSame([], $this->archiveFiles($archive));
        self::assertSame(0, $result->filesCreated);
        self::assertSame(0, $result->filesUpdated);
        self::assertSame(0, $result->keysInserted);
        self::assertSame(0, $result->keysUpdated);
        self::assertSame(0, $result->keysUnchanged);
    }

    public function test_it_merges_incoming_values_without_deleting_local_content(): void
    {
        $this->write('admin.en.yaml', "label.title: Old\nlabel.local_only: Keep\nlabel.same: Same\n");
        $archive = $this->archive([
            'admin.en.yaml' => "label.title: New\nlabel.remote_only: Insert\nlabel.same: Same\n",
            'form.en.yaml' => "form.save: Save\n",
        ]);

        $result = $this->manager()->import($archive);
        $codec = new FlatTranslationDocumentCodec();
        $admin = $codec->parse((string) file_get_contents($this->directory . DIRECTORY_SEPARATOR . 'admin.en.yaml'));

        self::assertSame(1, $result->filesCreated);
        self::assertSame(1, $result->filesUpdated);
        self::assertSame(2, $result->keysInserted);
        self::assertSame(1, $result->keysUpdated);
        self::assertSame(1, $result->keysUnchanged);
        self::assertSame('Keep', $admin['label.local_only']);
        self::assertSame('New', $admin['label.title']);
    }

    public function test_it_validates_the_complete_package_before_writing_any_file(): void
    {
        $this->write('admin.en.yaml', "label.title: Original\n");
        $archive = $this->archive([
            'admin.en.yaml' => "label.title: Changed\n",
            'form.en.yaml' => "form:\n  save: Invalid\n",
        ]);

        try {
            $this->manager()->import($archive);
            self::fail('An invalid package must be rejected.');
        } catch (TranslationSynchronizationException) {
            self::assertSame(
                "label.title: Original\n",
                file_get_contents($this->directory . DIRECTORY_SEPARATOR . 'admin.en.yaml'),
            );
            self::assertFileDoesNotExist($this->directory . DIRECTORY_SEPARATOR . 'form.en.yaml');
        }
    }

    public function test_it_rejects_archive_paths_outside_the_translation_root(): void
    {
        $archive = $this->archive(['../admin.en.yaml' => "label.title: Invalid\n"]);

        $this->expectException(TranslationSynchronizationException::class);
        $this->manager()->import($archive);
    }

    private function manager(): TranslationPackageManager
    {
        return new TranslationPackageManager(
            $this->directory,
            $this->options(),
            new FlatTranslationDocumentCodec(),
            new AtomicTranslationFileWriter(),
        );
    }

    private function options(): TranslationSynchronizationOptions
    {
        return new TranslationSynchronizationOptions(
            enabled: false,
            environment: 'test',
            appSecret: 'secret',
            deployedAppUrl: null,
            apiPath: '/api/translations',
            authSalt: null,
            signatureTtl: 300,
            timeout: 30,
            maxPackageBytes: 1048576,
            maxFiles: 20,
            basicAuthUsername: null,
            basicAuthPassword: null,
        );
    }

    /** @param array<string, string> $files */
    private function archive(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lexio-package-test-');
        self::assertIsString($path);
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));
        foreach ($files as $filename => $content) {
            self::assertTrue($zip->addFromString($filename, $content));
        }
        self::assertTrue($zip->close());
        $archive = file_get_contents($path);
        unlink($path);
        self::assertIsString($archive);

        return $archive;
    }

    /** @return array<string, string> */
    private function archiveFiles(string $archive): array
    {
        $path = tempnam(sys_get_temp_dir(), 'lexio-package-read-');
        self::assertIsString($path);
        self::assertNotFalse(file_put_contents($path, $archive));
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));
        $files = [];
        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $filename = $zip->getNameIndex($index);
            $content = $zip->getFromIndex($index);
            self::assertIsString($filename);
            self::assertIsString($content);
            $files[$filename] = $content;
        }
        self::assertTrue($zip->close());
        self::assertTrue(unlink($path));

        return $files;
    }

    private function write(string $filename, string $content): void
    {
        self::assertNotFalse(file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename, $content));
    }
}
