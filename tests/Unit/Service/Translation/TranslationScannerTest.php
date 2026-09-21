<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\TranslationScanException;
use Lexio\AdminBundle\Service\Translation\TranslationScanner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class TranslationScannerTest extends TestCase
{
    private string $projectDirectory;
    private string $translationDirectory;

    protected function setUp(): void
    {
        $this->projectDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-translation-scan-' . bin2hex(random_bytes(8));
        $this->translationDirectory = $this->projectDirectory . DIRECTORY_SEPARATOR . 'translations';

        self::assertTrue(mkdir($this->projectDirectory . DIRECTORY_SEPARATOR . 'templates', 0777, true));
        self::assertTrue(mkdir($this->translationDirectory, 0777, true));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->projectDirectory);
    }

    public function test_it_finds_hardcoded_twig_text_and_translatable_attributes(): void
    {
        $this->write('templates/checkout/index.html.twig', <<<'TWIG'
{# This comment is ignored. #}
<h1>Checkout</h1>
<input placeholder="Search products">
{% trans %}Already translated{% endtrans %}
<script>alert('Ignored');</script>
{# translation-scan-ignore-start #}
<p>Ignored copy</p>
{# translation-scan-ignore-end #}
TWIG);

        $findings = $this->scanner()->scan('templates/checkout', 3, []);

        self::assertSame([
            [
                'file' => 'templates/checkout/index.html.twig',
                'line' => 2,
                'type' => 'text',
                'value' => 'Checkout',
            ],
            [
                'file' => 'templates/checkout/index.html.twig',
                'line' => 3,
                'type' => 'attribute',
                'attribute' => 'placeholder',
                'value' => 'Search products',
            ],
        ], $findings);
    }

    public function test_it_finds_placeholder_values_in_all_managed_locales(): void
    {
        $this->write('translations/admin.bg.yaml', "admin.title: __admin.title\nadmin.description: \"Ready\"\n");
        $this->write('translations/admin.en.yaml', "admin.title: \"__admin.title\"\n__admin.key: Visible\n");
        $this->write('translations/admin.fr.yml', "admin.title: __ignored\n");
        $this->write('translations/ignored.yaml', "admin.title: __ignored\n");

        $findings = $this->scanner()->scan(null, 20, []);

        self::assertSame([
            [
                'file' => 'translations/admin.bg.yaml',
                'line' => 1,
                'type' => 'placeholder',
                'key' => 'admin.title',
                'value' => '__admin.title',
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

    public function test_it_accepts_quoted_translation_keys_containing_spaces(): void
    {
        $this->write(
            'translations/messages.bg.yaml',
            "'Your email is already confirmed': 'Вашият имейл вече е потвърден.'\n",
        );

        self::assertSame([], $this->scanner()->scan(null, 2, []));
    }

    public function test_it_does_not_apply_the_twig_minimum_length_to_placeholder_values(): void
    {
        $this->write('templates/short.twig', '<p>Go</p>');
        $this->write('translations/admin.en.yaml', "admin.short: __\n");

        $findings = $this->scanner()->scan(null, 3, []);

        self::assertSame([
            [
                'file' => 'translations/admin.en.yaml',
                'line' => 1,
                'type' => 'placeholder',
                'key' => 'admin.short',
                'value' => '__',
            ],
        ], $findings);
    }

    public function test_it_limits_an_explicit_path_to_its_matching_content_type(): void
    {
        $this->write('templates/checkout/index.html.twig', '<p>Checkout</p>');
        $this->write('translations/admin.en.yaml', "admin.title: __admin.title\n");

        self::assertSame([
            [
                'file' => 'templates/checkout/index.html.twig',
                'line' => 1,
                'type' => 'text',
                'value' => 'Checkout',
            ],
        ], $this->scanner()->scan('templates/checkout', 2, []));

        self::assertSame([
            [
                'file' => 'translations/admin.en.yaml',
                'line' => 1,
                'type' => 'placeholder',
                'key' => 'admin.title',
                'value' => '__admin.title',
            ],
        ], $this->scanner()->scan('translations/admin.en.yaml', 2, []));

        self::assertSame([
            [
                'file' => 'templates/checkout/index.html.twig',
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
        ], $this->scanner()->scan('.', 2, []));
    }

    public function test_it_excludes_named_template_directories(): void
    {
        $this->write('templates/emails/receipt.html.twig', '<p>Email receipt</p>');
        $this->write('templates/checkout/index.html.twig', '<p>Checkout</p>');

        $findings = $this->scanner()->scan(null, 2, ['emails']);

        self::assertSame([
            [
                'file' => 'templates/checkout/index.html.twig',
                'line' => 1,
                'type' => 'text',
                'value' => 'Checkout',
            ],
        ], $findings);
    }

    public function test_it_rejects_invalid_managed_translation_documents(): void
    {
        $this->write('translations/admin.en.yaml', "admin:\n  title: __admin.title\n");

        $this->expectException(TranslationScanException::class);
        $this->expectExceptionMessage('translations/admin.en.yaml');

        $this->scanner()->scan(null, 2, []);
    }

    public function test_it_rejects_scans_when_translation_management_is_disabled(): void
    {
        $this->expectException(TranslationScanException::class);
        $this->expectExceptionMessage('Translation management is disabled.');

        $this->scanner(enabled: false)->scan(null, 2, []);
    }

    private function scanner(bool $enabled = true): TranslationScanner
    {
        return new TranslationScanner(
            projectDirectory: $this->projectDirectory,
            translationDirectory: $this->translationDirectory,
            enabled: $enabled,
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
