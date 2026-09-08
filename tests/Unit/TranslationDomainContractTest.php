<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit;

use Lexio\AdminBundle\LexioAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TranslationDomainContractTest extends TestCase
{
    public function test_bundle_does_not_ship_translation_catalogs(): void
    {
        $translations = dirname(__DIR__, 2) . '/translations';

        self::assertFileDoesNotExist($translations . '/LexioAdminBundle.en.yaml');
        self::assertFileDoesNotExist($translations . '/LexioAdminBundle.bg.yaml');
    }

    public function test_ui_configuration_does_not_expose_translation_keys_or_domains(): void
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();

        self::assertNotNull($extension);
        $configuration = $extension->getConfiguration([], $container);
        self::assertNotNull($configuration);

        $processed = (new Processor())->processConfiguration($configuration, [[]]);

        foreach ([
            'admin_logo_alt',
            'title_translation_key',
            'title_translation_domain',
            'translation_domain',
        ] as $removedOption) {
            self::assertArrayNotHasKey($removedOption, $processed['ui']);
        }
    }

    public function test_bundle_templates_use_admin_or_form_translation_domains(): void
    {
        foreach ($this->templateFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            self::assertStringNotContainsString('LexioAdminBundle', $source);
            self::assertStringNotContainsString('lexio_admin_ui.translation_domain', $source);

            if (str_ends_with(str_replace('\\', '/', $path), '/templates/form/custom_fields_theme.html.twig')) {
                self::assertStringContainsString("'form'", $source);

                continue;
            }

            if (str_contains($source, '|trans')) {
                self::assertStringContainsString("'admin'", $source);
            }
        }
    }

    public function test_bundle_form_types_use_the_form_translation_domain(): void
    {
        $directory = dirname(__DIR__, 2) . '/src/Form';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);

            if (!str_contains($source, 'extends AbstractType')) {
                continue;
            }

            self::assertStringContainsString(
                "'translation_domain' => 'form'",
                $source,
                sprintf('%s must use the form translation domain.', $file->getPathname()),
            );
        }
    }

    public function test_menu_controller_creates_forms_with_the_form_domain(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/src/Controller/Admin/MenuController.php');

        self::assertIsString($source);
        self::assertStringNotContainsString(
            "'translation_domain' => \$this->translationDomain()",
            $source,
        );
        self::assertSame(4, substr_count($source, "'translation_domain' => 'form'"));
    }

    /** @return list<string> */
    private function templateFiles(): array
    {
        $directory = dirname(__DIR__, 2) . '/templates';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.html.twig')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}
