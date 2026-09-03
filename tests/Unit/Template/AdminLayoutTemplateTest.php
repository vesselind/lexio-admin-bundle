<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Template;

use PHPUnit\Framework\TestCase;

final class AdminLayoutTemplateTest extends TestCase
{
    public function test_admin_layout_is_debug_free_and_uses_the_documented_ui_contract(): void
    {
        $layout = dirname(__DIR__, 3) . '/templates/admin_base.html.twig';

        self::assertFileExists($layout);

        $source = file_get_contents($layout);

        self::assertIsString($source);
        self::assertDoesNotMatchRegularExpression('/\b(?:dump|dd)\s*\(/', $source);
        self::assertStringContainsString('lexio_admin_ui.favicon_asset', $source);
        self::assertStringContainsString('lexio_admin_ui.admin_logo_asset', $source);
        self::assertStringContainsString('lexio_admin_ui.routes.header_search', $source);
        self::assertStringContainsString("@LexioAdmin/admin/flash.stream.html.twig", $source);
        self::assertStringNotContainsString("asset('build/images/logo.svg')", $source);
        self::assertStringNotContainsString("asset('build/images/admin-logo.svg')", $source);
    }

    public function test_application_child_fixture_overrides_a_stable_block_without_copying_the_layout(): void
    {
        $child = dirname(__DIR__, 3) . '/tests/Fixtures/templates/admin_layout_child.html.twig';
        $source = file_get_contents($child);

        self::assertIsString($source);
        self::assertStringContainsString(
            "{% extends '@LexioAdmin/admin_base.html.twig' %}",
            $source,
        );
        self::assertStringContainsString('{% block header_logo %}', $source);
        self::assertStringNotContainsString('<!DOCTYPE html>', $source);
    }

}
