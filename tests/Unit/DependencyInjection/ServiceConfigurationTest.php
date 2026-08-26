<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\DependencyInjection;

use Lexio\AdminBundle\Service\EntityFilterer;
use Lexio\AdminBundle\File\ImageCacheResolver;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\Contract\Translation\TranslationCatalogInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Service\Translation\TranslationPackageSynchronizer;
use Lexio\AdminBundle\Service\Translation\TranslationCacheClearer;
use Lexio\AdminBundle\Service\Translation\YamlTranslationCatalog;
use PHPUnit\Framework\TestCase;

final class ServiceConfigurationTest extends TestCase
{
    public function test_filter_service_configuration_references_the_existing_service(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(EntityFilterer::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Service\\EntityFilterer:',
            $servicesConfig,
        );
        self::assertStringNotContainsString(
            'Lexio\\AdminBundle\\Service\\EntityFilterService:',
            $servicesConfig,
        );
    }

    public function test_image_cache_resolver_configuration_references_the_existing_service(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(ImageCacheResolver::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\File\\ImageCacheResolver:',
            $servicesConfig,
        );
        self::assertStringNotContainsString(
            'Lexio\\AdminBundle\\Service\\File\\ImageCacheResolver:',
            $servicesConfig,
        );
    }

    public function test_file_manager_configuration_references_the_existing_service(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(FileManager::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\File\\FileManager:',
            $servicesConfig,
        );
        self::assertStringNotContainsString(
            'Lexio\\AdminBundle\\Service\\File\\FileManager:',
            $servicesConfig,
        );
    }

    public function test_translation_catalog_configuration_references_the_public_contract(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(interface_exists(TranslationCatalogInterface::class));
        self::assertTrue(class_exists(YamlTranslationCatalog::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Contract\\Translation\\TranslationCatalogInterface:',
            $servicesConfig,
        );
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Service\\Translation\\YamlTranslationCatalog:',
            $servicesConfig,
        );
    }

    public function test_translation_package_synchronizer_configuration_references_the_public_contract(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(interface_exists(TranslationPackageSynchronizerInterface::class));
        self::assertTrue(class_exists(TranslationPackageSynchronizer::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Contract\\Translation\\TranslationPackageSynchronizerInterface:',
            $servicesConfig,
        );
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Service\\Translation\\TranslationPackageSynchronizer',
            $servicesConfig,
        );
    }

    public function test_translation_cache_clearer_is_bound_to_the_kernel_cache_directory(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(TranslationCacheClearer::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Service\\Translation\\TranslationCacheClearer:',
            $servicesConfig,
        );
        self::assertStringContainsString('$cacheDirectory: \'%kernel.cache_dir%\'', $servicesConfig);
    }
}
