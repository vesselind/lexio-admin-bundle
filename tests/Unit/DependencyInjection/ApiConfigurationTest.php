<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\DependencyInjection;

use Lexio\AdminBundle\Api\ApiRequestMatcher;
use Lexio\AdminBundle\Api\MapTransformer\FileUrlTransformer;
use Lexio\AdminBundle\Api\Service\ApiKeyGenerator;
use Lexio\AdminBundle\Api\Service\DoctrineApiKeyUserProvider;
use Lexio\AdminBundle\Contract\Api\ApiKeyUserProviderInterface;
use Lexio\AdminBundle\EventListener\UserApiKeyListener;
use Lexio\AdminBundle\EventSubscriber\ApiExceptionSubscriber;
use Lexio\AdminBundle\EventSubscriber\ApiLocaleSubscriber;
use Lexio\AdminBundle\LexioAdminBundle;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use Composer\Autoload\ClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ApiConfigurationTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_disabled_api_services_compile_without_the_optional_mapper_package(): void
    {
        $this->hideObjectMapper();
        self::assertFalse(interface_exists(\Symfony\Component\ObjectMapper\ObjectMapperInterface::class));
        $container = $this->load([]);
        // Isolate the API definitions from the bundle's unrelated host integrations.
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!in_array($id, ['service_container', ApiRequestMatcher::class], true)) {
                $container->removeDefinition($id);
            }
        }
        foreach (array_keys($container->getAliases()) as $id) {
            $container->removeAlias($id);
        }
        $container->getDefinition(ApiRequestMatcher::class)->setPublic(true);
        $container->compile();
        self::assertTrue($container->get(ApiRequestMatcher::class)->matches(\Symfony\Component\HttpFoundation\Request::create('/api/v1/files/list')));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_enabled_api_reports_its_missing_optional_dependency(): void
    {
        $this->hideObjectMapper();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('requires symfony/object-mapper');
        $this->load(['api' => ['enabled' => true]]);
    }

    public function test_api_is_disabled_by_default_and_needs_no_api_user(): void
    {
        $container = $this->load([]);
        self::assertTrue($container->hasDefinition(ApiRequestMatcher::class));
        self::assertFalse($container->hasDefinition(FileUrlTransformer::class));
        self::assertTrue($container->getDefinition(ApiLocaleSubscriber::class)->isAbstract());
        self::assertTrue($container->getDefinition(ApiExceptionSubscriber::class)->isAbstract());
        self::assertFalse($container->hasDefinition(ApiKeyGenerator::class));
        self::assertTrue($container->getDefinition(UserApiKeyListener::class)->isAbstract());
        self::assertFalse($container->hasAlias(ApiKeyUserProviderInterface::class));
    }

    public function test_api_core_can_be_enabled_without_api_key_security(): void
    {
        $container = $this->load(['api' => ['enabled' => true, 'path_prefix' => '/bridge/v2']]);
        self::assertTrue($container->hasDefinition(FileUrlTransformer::class));
        self::assertTrue($container->hasDefinition(ApiExceptionSubscriber::class));
        self::assertTrue($container->hasDefinition(ApiLocaleSubscriber::class));
        self::assertSame('%lexio_admin.locales%', $container->getDefinition(ApiLocaleSubscriber::class)->getArgument('$locales'));
        self::assertFalse($container->hasDefinition(ApiKeyGenerator::class));
        self::assertSame('/bridge/v2', $container->getParameter('lexio_admin.api.path_prefix'));
    }

    public function test_security_uses_configured_user_and_opt_in_listener(): void
    {
        $container = $this->load([
            'user_entity_class' => PortableUser::class,
            'api' => ['enabled' => true, 'security' => ['enabled' => true, 'registration_listener' => true]],
        ]);
        self::assertTrue($container->hasDefinition(ApiKeyGenerator::class));
        self::assertSame(PortableUser::class, $container->getDefinition(DoctrineApiKeyUserProvider::class)->getArgument('$userEntityClass'));
        self::assertSame(UserApiKeyListener::class, $container->getDefinition(UserApiKeyListener::class)->getClass());
        self::assertSame('Lexio\AdminBundle\Event\Security\UserHasRegistered', $container->getDefinition(UserApiKeyListener::class)->getTag('kernel.event_listener')[0]['event']);
    }

    public function test_custom_provider_needs_no_doctrine_user_class(): void
    {
        $container = $this->load(['api' => ['enabled' => true, 'security' => [
            'enabled' => true, 'user_provider' => 'project.api_users',
        ]]]);
        self::assertSame('project.api_users', (string) $container->getAlias(ApiKeyUserProviderInterface::class));
        self::assertFalse($container->hasDefinition(DoctrineApiKeyUserProvider::class));
        self::assertTrue($container->getDefinition(UserApiKeyListener::class)->isAbstract());
    }

    public function test_security_requires_api_core_to_be_enabled(): void
    {
        $this->expectException(\LogicException::class);
        $this->load(['api' => ['security' => ['enabled' => true]]]);
    }

    public function test_default_provider_rejects_a_user_without_api_capability(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('ApiKeyUserInterface');
        $this->load(['user_entity_class' => \stdClass::class, 'api' => ['enabled' => true, 'security' => ['enabled' => true]]]);
    }

    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 3));
        $container->setParameter('kernel.debug', false);
        (new LexioAdminBundle())->getContainerExtension()->load([$config], $container);

        return $container;
    }

    private function hideObjectMapper(): void
    {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->unregister();
            spl_autoload_register(static function (string $class) use ($loader): void {
                if (!str_starts_with($class, 'Symfony\Component\ObjectMapper\\')) {
                    $loader->loadClass($class);
                }
            });
        }
    }
}
