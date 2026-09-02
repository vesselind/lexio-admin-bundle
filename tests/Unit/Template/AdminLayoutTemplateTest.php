<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Template;

use PHPUnit\Framework\TestCase;
use Twig\Extension\AbstractExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AdminLayoutTemplateTest extends TestCase
{
    public function test_admin_layout_is_debug_free_and_uses_the_documented_ui_contract(): void
    {
        $layout = dirname(__DIR__, 3) . '/templates/admin_base.html.twig';

        self::assertFileExists($layout);

        $source = file_get_contents($layout);

        self::assertIsString($source);
        self::assertDoesNotMatchRegularExpression('/\b(?:dump|dd)\s*\(/', $source);
        self::assertStringContainsString('lexio_admin_ui.seo_template', $source);
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

    public function test_configured_seo_template_is_rendered_by_the_canonical_layout(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 3) . '/templates', 'LexioAdmin');
        $loader->addPath(dirname(__DIR__, 3) . '/tests/Fixtures/templates', 'Fixtures');

        $request = new class {
            public string $locale = 'en';
            public string $uri = 'https://example.test/admin';
            public object $attributes;
            public object $headers;

            public function __construct()
            {
                $this->attributes = new class {
                    public function get(string $name): mixed
                    {
                        return null;
                    }
                };
                $this->headers = new class {
                    public function get(string $name): mixed
                    {
                        return null;
                    }
                };
            }
        };
        $app = new class($request) {
            public function __construct(public object $request)
            {
            }

            /** @return list<string> */
            public function flashes(string $type): array
            {
                return [];
            }
        };

        $twig = new Environment($loader);
        $twig->addExtension(new class extends AbstractExtension {
            /** @return list<TwigFilter> */
            public function getFilters(): array
            {
                return [
                    new TwigFilter('trans', static fn (string $message): string => $message),
                ];
            }

            /** @return list<TwigFunction> */
            public function getFunctions(): array
            {
                return [
                    new TwigFunction('asset', static fn (?string $path): string => $path ?? ''),
                    new TwigFunction('csrf_token', static fn (string $tokenId): string => $tokenId),
                    new TwigFunction('encore_entry_link_tags', static fn (string $entry): string => ''),
                    new TwigFunction('encore_entry_script_tags', static fn (string $entry): string => ''),
                    new TwigFunction('is_granted', static fn (string $attribute): bool => false),
                    new TwigFunction('path', static fn (?string $route): string => $route ?? ''),
                    new TwigFunction('stimulus_controller', static fn (string $controller): string => $controller),
                    new TwigFunction('url', static fn (?string $route): string => $route ?? ''),
                    new TwigFunction('wo_render_breadcrumbs', static fn (): string => ''),
                ];
            }
        });
        $twig->addGlobal('app', $app);
        $twig->addGlobal('lexio_admin_ui', [
            'seo_template' => '@Fixtures/configured_seo.html.twig',
            'favicon_asset' => null,
            'admin_logo_asset' => null,
            'admin_logo_alt' => 'admin.logo_alt',
            'title_translation_key' => null,
            'title_translation_domain' => 'LexioAdminBundle',
            'translation_domain' => 'LexioAdminBundle',
            'routes' => [],
        ]);

        $html = $twig->render('@Fixtures/admin_layout_render_child.html.twig');

        self::assertStringContainsString('<meta name="test-seo" content="enabled">', $html);
        self::assertStringContainsString('data-test="rendered-main"', $html);
    }
}
