<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form\CustomFields;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class CustomFieldFormThemeTest extends TestCase
{
    private const THEME_PATH = __DIR__ . '/../../../../templates/form/custom_fields_theme.html.twig';

    public function test_theme_defines_blocks_matching_the_bundle_form_types(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__ . '/../../../../templates', 'LexioAdmin');

        $twig = new Environment($loader);
        foreach (['form_label', 'form_widget', 'path', 'stimulus_controller'] as $function) {
            $twig->addFunction(new TwigFunction($function, static fn (mixed ...$arguments): string => ''));
        }
        foreach (['stimulus_controller', 'trans'] as $filter) {
            $twig->addFilter(new TwigFilter($filter, static fn (mixed ...$arguments): string => ''));
        }

        $blocks = $twig
            ->load('@LexioAdmin/form/custom_fields_theme.html.twig')
            ->unwrap()
            ->getBlocks();

        self::assertArrayHasKey('association_modal_widget', $blocks);
        self::assertArrayHasKey('input_image_selector_widget', $blocks);
        self::assertArrayHasKey('ck_editor_label', $blocks);
    }

    public function test_theme_preserves_the_existing_frontend_integration_contract(): void
    {
        $theme = file_get_contents(self::THEME_PATH);

        self::assertIsString($theme);
        self::assertStringNotContainsString("{% extends 'bootstrap_5_layout.html.twig' %}", $theme);
        self::assertStringContainsString("stimulus_controller('association-modal-type'", $theme);
        self::assertStringContainsString("stimulus_controller('open-base-modal'", $theme);
        self::assertStringContainsString("stimulus_controller('links-search-field'", $theme);
        self::assertStringContainsString('<twig:Admin:InputImageSelector', $theme);
        self::assertStringContainsString('visually-hidden', $theme);
        $componentTemplate = file_get_contents(__DIR__ . '/../../../../templates/components/Admin/InputImageSelector.html.twig');

        self::assertIsString($componentTemplate);
        self::assertStringContainsString('input-image-selector__card', $componentTemplate);
        self::assertStringContainsString('path(lexio_admin_ui.routes.links_search', $theme);
        self::assertStringContainsString('{% if linksSearch %}', $theme);
        self::assertStringContainsString('aria-label="{{ modalTitle|trans({}, lexio_admin_ui.translation_domain) }}"', $theme);
        self::assertStringContainsString('aria-label="{{ \'button.close\'|trans({}, lexio_admin_ui.translation_domain) }}"', $theme);
        self::assertStringNotContainsString('aria-label="Close"', $theme);
        self::assertStringNotContainsString('id="exampleModalLabel"', $theme);
    }
}
