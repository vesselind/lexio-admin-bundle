<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationCatalogException;
use Lexio\AdminBundle\Service\Translation\AtomicTranslationFileWriter;
use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\YamlTranslationCatalog;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class YamlTranslationCatalogTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-translation-' . bin2hex(random_bytes(8));
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

    public function test_it_lists_only_supported_translation_files_and_reads_flat_entries(): void
    {
        $this->write('admin.bg.yaml', "label.title: 'Заглавие'\nlabel.info: Инфо\n");
        $this->write('admin.en.yaml', "label.title: Title\n");
        $this->write('admin.bg.yml', "label.title: ignored\n");
        $this->write('admin.bg.yaml.bak', "label.title: ignored\n");

        $catalog = $this->catalog();
        $files = array_values(iterator_to_array($catalog->listFiles()));
        $entries = array_values(iterator_to_array($catalog->getEntries('admin', 'bg')));

        self::assertCount(2, $files);
        self::assertSame('admin.bg.yaml', $files[0]->filename);
        self::assertSame('label.title', $entries[0]->key);
        self::assertSame('Заглавие', $entries[0]->value);
        self::assertSame('label.info', $entries[1]->key);
    }

    public function test_it_updates_one_value_and_keeps_yaml_valid_for_significant_and_multiline_text(): void
    {
        $this->write('admin.bg.yaml', "label.title: Original\nlabel.info: Info\n");
        $catalog = $this->catalog();

        $catalog->update('admin', 'bg', 'label.title', "Text: # comment\nSecond line's value");

        $parsed = Yaml::parseFile($this->directory . DIRECTORY_SEPARATOR . 'admin.bg.yaml');
        self::assertIsArray($parsed);
        self::assertSame("Text: # comment\nSecond line's value", $parsed['label.title']);
        self::assertSame('Info', $parsed['label.info']);
        self::assertStringContainsString("label.info: Info\n", (string) file_get_contents($this->directory . DIRECTORY_SEPARATOR . 'admin.bg.yaml'));
        $entries = array_values(iterator_to_array($catalog->getEntries('admin', 'bg')));
        self::assertSame("Text: # comment\nSecond line's value", $entries[0]->value);
    }

    public function test_it_does_not_leave_inline_mapping_braces_when_updating_a_scalar_value(): void
    {
        $this->write('admin.bg.yaml', "translation.select_file: 'Избери файл'\n");
        $catalog = $this->catalog();

        $catalog->update('admin', 'bg', 'translation.select_file', 'Избери файл2');

        $content = (string) file_get_contents($this->directory . DIRECTORY_SEPARATOR . 'admin.bg.yaml');
        self::assertStringNotContainsString(' }', $content);
        $parsed = Yaml::parse($content);
        self::assertIsArray($parsed);
        self::assertSame('Избери файл2', $parsed['translation.select_file']);
    }

    public function test_it_rejects_nested_records(): void
    {
        $this->write('admin.bg.yaml', "label:\n  title: Nested\n");
        $catalog = $this->catalog();

        $this->expectException(TranslationCatalogException::class);
        iterator_to_array($catalog->getEntries('admin', 'bg'));
    }

    public function test_it_rejects_duplicate_records(): void
    {
        $this->write('admin.en.yaml', "label.title: First\nlabel.title: Second\n");
        $catalog = $this->catalog();

        $this->expectException(TranslationCatalogException::class);
        iterator_to_array($catalog->getEntries('admin', 'en'));
    }

    public function test_it_rejects_unknown_keys_and_path_traversal(): void
    {
        $this->write('admin.bg.yaml', "label.title: Title\n");
        $catalog = $this->catalog();

        try {
            $catalog->update('admin', 'bg', 'label.missing', 'Value');
            self::fail('An unknown key must be rejected.');
        } catch (TranslationCatalogException) {
            self::assertTrue(true);
        }

        $this->expectException(TranslationCatalogException::class);
        $catalog->update('../outside', 'bg', 'label.title', 'Value');
    }

    private function catalog(): YamlTranslationCatalog
    {
        return new YamlTranslationCatalog(
            $this->directory,
            true,
            new FlatTranslationDocumentCodec(),
            new AtomicTranslationFileWriter(),
        );
    }

    private function write(string $filename, string $content): void
    {
        self::assertNotFalse(file_put_contents($this->directory . DIRECTORY_SEPARATOR . $filename, $content));
    }
}
