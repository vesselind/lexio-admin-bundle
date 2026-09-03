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
use Lexio\AdminBundle\Page\PageManager;
use Lexio\AdminBundle\AdminCore\Resolver\MapQueryStringValueResolver;
use Lexio\AdminBundle\Component\Admin\InputImageSelector;
use Lexio\AdminBundle\Command\ProdDeployCommand;
use Lexio\AdminBundle\Contract\Deployment\DeploymentRunnerInterface;
use Lexio\AdminBundle\Form\CustomFields\CaptchaType;
use Lexio\AdminBundle\Form\CustomFields\TurnstileType;
use Lexio\AdminBundle\Service\Deployment\ProcessRunnerInterface;
use Lexio\AdminBundle\Service\Deployment\SymfonyProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelEvents;

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

    public function test_page_manager_configuration_references_the_existing_service(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(PageManager::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Page\\PageManager:',
            $servicesConfig,
        );
        self::assertStringNotContainsString(
            'Lexio\\AdminBundle\\Page\\Service\\PageManager:',
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

    public function test_turnstile_type_is_registered_with_its_provider_specific_name(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(TurnstileType::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Form\\CustomFields\\TurnstileType:',
            $servicesConfig,
        );
    }

    public function test_google_recaptcha_type_is_registered_as_a_separate_service(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(CaptchaType::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Form\\CustomFields\\CaptchaType:',
            $servicesConfig,
        );
        self::assertStringContainsString(
            '$defaultSiteKey: \'%env(default::GOOGLE_RECAPTCHA_SITE_KEY)%\'',
            $servicesConfig,
        );
    }

    public function test_input_image_selector_component_is_registered_with_a_stable_name(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(InputImageSelector::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Component\\Admin\\InputImageSelector:',
            $servicesConfig,
        );
        self::assertStringContainsString("name: twig.component, key: 'Admin:InputImageSelector'", $servicesConfig);
        self::assertStringContainsString(
            "template: '@LexioAdmin/components/Admin/InputImageSelector.html.twig'",
            $servicesConfig,
        );
    }

    public function test_map_query_string_resolver_is_registered_as_an_event_subscriber(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(MapQueryStringValueResolver::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\AdminCore\\Resolver\\MapQueryStringValueResolver:',
            $servicesConfig,
        );
        self::assertStringContainsString('name: kernel.event_subscriber', $servicesConfig);
        self::assertTrue(
            is_subclass_of(MapQueryStringValueResolver::class, EventSubscriberInterface::class),
        );
    }

    public function test_map_query_string_resolver_service_passes_event_subscriber_compilation(): void
    {
        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcher::class);
        $container
            ->register(MapQueryStringValueResolver::class, MapQueryStringValueResolver::class)
            ->addTag('kernel.event_subscriber');

        (new RegisterListenersPass())->process($container);

        $methodCalls = $container->getDefinition('event_dispatcher')->getMethodCalls();

        self::assertCount(1, $methodCalls);
        self::assertSame('addListener', $methodCalls[0][0]);
        self::assertSame(KernelEvents::CONTROLLER_ARGUMENTS, $methodCalls[0][1][0]);
        self::assertSame('onKernelControllerArguments', $methodCalls[0][1][1][1]);
    }

    public function test_deployment_command_and_runner_contracts_are_registered(): void
    {
        $servicesConfig = file_get_contents(__DIR__ . '/../../../config/services.yaml');

        self::assertIsString($servicesConfig);
        self::assertTrue(class_exists(ProdDeployCommand::class));
        self::assertTrue(interface_exists(DeploymentRunnerInterface::class));
        self::assertTrue(interface_exists(ProcessRunnerInterface::class));
        self::assertTrue(class_exists(SymfonyProcessRunner::class));
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Command\\ProdDeployCommand:',
            $servicesConfig,
        );
        self::assertStringContainsString('command: prod:deploy', $servicesConfig);
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Contract\\Deployment\\DeploymentRunnerInterface:',
            $servicesConfig,
        );
        self::assertStringContainsString(
            'Lexio\\AdminBundle\\Service\\Deployment\\ProcessRunnerInterface:',
            $servicesConfig,
        );
    }
}
