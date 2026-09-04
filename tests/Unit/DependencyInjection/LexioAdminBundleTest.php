<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\DependencyInjection;

use Lexio\AdminBundle\LexioAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\Configuration as TwigConfiguration;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class LexioAdminBundleTest extends TestCase
{
    public function test_ui_configuration_exposes_canonical_customization_contract_defaults(): void
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();

        self::assertNotNull($extension);

        $configuration = $extension->getConfiguration([], $container);

        self::assertNotNull($configuration);

        $processed = (new Processor())->processConfiguration($configuration, [[]]);

        self::assertSame('LexioAdminBundle', $processed['ui']['translation_domain']);
        self::assertNull($processed['ui']['favicon_asset']);
        self::assertNull($processed['ui']['admin_logo_asset']);
        self::assertSame('admin.header_search', $processed['ui']['routes']['header_search']);
        self::assertSame('admin.system_notification.index', $processed['ui']['routes']['notification_index']);
        self::assertSame('flash.create', $processed['ui']['routes']['flash']);
        self::assertSame('admin.ckeditor.upload', $processed['ui']['routes']['ckeditor_upload']);
        self::assertArrayNotHasKey('image_index', $processed['ui']['routes']);
        self::assertArrayNotHasKey('image_upload', $processed['ui']['routes']);
        self::assertArrayNotHasKey('image_download', $processed['ui']['routes']);
        self::assertArrayNotHasKey('image_delete', $processed['ui']['routes']);
        self::assertArrayNotHasKey('image_modal_gallery', $processed['ui']['routes']);
        self::assertArrayNotHasKey('image_modal_upload', $processed['ui']['routes']);
        self::assertArrayNotHasKey('file_index', $processed['ui']['routes']);
        self::assertArrayNotHasKey('file_upload', $processed['ui']['routes']);
        self::assertArrayNotHasKey('file_download', $processed['ui']['routes']);
        self::assertArrayNotHasKey('file_delete', $processed['ui']['routes']);
    }

    public function test_ui_configuration_accepts_host_route_and_asset_overrides(): void
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();

        self::assertNotNull($extension);

        $configuration = $extension->getConfiguration([], $container);

        self::assertNotNull($configuration);

        $processed = (new Processor())->processConfiguration($configuration, [[
            'front_home_page_route' => 'app.home',
            'ui' => [
                'favicon_asset' => 'build/favicon.svg',
                'admin_logo_asset' => 'build/admin-logo.svg',
                'routes' => [
                    'header_search' => 'app.admin_search',
                    'notification_index' => 'app.notifications',
                    'ckeditor_upload' => 'app.ckeditor_upload',
                ],
            ],
        ]]);

        self::assertSame('build/favicon.svg', $processed['ui']['favicon_asset']);
        self::assertSame('build/admin-logo.svg', $processed['ui']['admin_logo_asset']);
        self::assertSame('app.admin_search', $processed['ui']['routes']['header_search']);
        self::assertSame('app.notifications', $processed['ui']['routes']['notification_index']);
        self::assertSame('app.ckeditor_upload', $processed['ui']['routes']['ckeditor_upload']);
    }

    public function test_custom_field_theme_is_prepended_before_host_form_themes(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TwigExtension());
        $container->loadFromExtension('twig', [
            'form_themes' => ['app_form_theme.html.twig'],
        ]);

        $bundle = new LexioAdminBundle();
        $extension = $bundle->getContainerExtension();

        if (!$extension instanceof PrependExtensionInterface) {
            self::fail('The bundle extension must support prepending Twig configuration.');
        }

        $extension->prepend($container);

        $twigConfigurations = $container->getExtensionConfig('twig');

        self::assertSame(
            ['@LexioAdmin/form/custom_fields_theme.html.twig'],
            $twigConfigurations[0]['form_themes'] ?? null,
        );
        self::assertSame(
            ['app_form_theme.html.twig'],
            $twigConfigurations[1]['form_themes'] ?? null,
        );
        self::assertSame(
            [$bundle->getPath() . '/templates' => 'LexioAdmin'],
            $twigConfigurations[2]['paths'] ?? null,
        );

        $processedConfiguration = (new Processor())->processConfiguration(
            new TwigConfiguration(),
            $twigConfigurations,
        );

        self::assertSame([
            'form_div_layout.html.twig',
            '@LexioAdmin/form/custom_fields_theme.html.twig',
            'app_form_theme.html.twig',
        ], $processedConfiguration['form_themes']);
    }
}
