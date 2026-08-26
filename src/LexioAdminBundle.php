<?php

declare(strict_types=1);

namespace Lexio\AdminBundle;

use Lexio\AdminBundle\DependencyInjection\Compiler\ResolveNotificationUserPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class LexioAdminBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        // Register bundle templates under the @LexioAdmin namespace.
        // Must be in prependExtension() so the config is available when TwigBundle loads.
        if ($container->hasExtension('twig')) {
            $configurator->extension('twig', [
                'paths' => [
                    $this->getPath() . '/templates' => 'LexioAdmin',
                ],
            ]);
        }

        // Register Twig component template path so UX resolves @LexioAdmin/components/
        if ($container->hasExtension('ux_twig_component')) {
            $configurator->extension('ux_twig_component', [
                'defaults' => [
                    'Lexio\\AdminBundle\\Component\\' => [
                        'template_directory' => '@LexioAdmin/components/',
                    ],
                ],
            ]);
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Compiler pass runs after all extensions are loaded — safe to reference Doctrine services
        $container->addCompilerPass(new ResolveNotificationUserPass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('default_locale')
                    ->defaultValue('en')
                    ->info('The default locale for the admin panel.')
                ->end()
                ->arrayNode('locales')
                    ->scalarPrototype()->end()
                    ->defaultValue(['en'])
                    ->info('Available locales for the admin panel.')
                ->end()
                ->scalarNode('admin_route_prefix')
                    ->defaultValue('/admin')
                    ->info('URL prefix for all admin routes (used by RouteLoader).')
                ->end()
                ->integerNode('listing_items_per_page')
                    ->defaultValue(20)
                    ->info('Default number of items per page in listing views.')
                ->end()
                ->scalarNode('deepl_translation_api_key')
                    ->defaultNull()
                    ->info('API key for DeepL translation service (optional, used for auto-translating content).')
                ->end()
                ->scalarNode('google_translation_api_key')
                    ->defaultNull()
                    ->info('API key for Google Translate service (optional, used for auto-translating content).')
                ->end()
                ->scalarNode('user_entity_class')
                    ->defaultNull()
                    ->info('FQCN of the User entity. Required when using HasRegisteredField.')
                ->end()
                ->scalarNode('front_home_page_route')
                    ->defaultValue('static_pages.home_page')
                    ->info('Route name for the front home page. Used by the breadcrumbs and others.')
                ->end()
                ->arrayNode('translation_management')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultTrue()
                            ->info('Enable administration of flat YAML translation files.')
                        ->end()
                        ->scalarNode('translation_directory')
                            ->defaultNull()
                            ->info('Absolute path to the host translation directory. Defaults to %kernel.project_dir%/translations.')
                        ->end()
                        ->arrayNode('synchronization')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('enabled')
                                    ->defaultFalse()
                                    ->info('Enable signed translation package API and outbound synchronization.')
                                ->end()
                                ->scalarNode('deployed_app_url')
                                    ->defaultNull()
                                    ->info('Base URL of the deployed host application used by upload and download commands.')
                                ->end()
                                ->scalarNode('api_path')
                                    ->defaultValue('/api/translations')
                                    ->cannotBeEmpty()
                                    ->info('Unlocalized relative path of the translation package API.')
                                ->end()
                                ->scalarNode('auth_salt')
                                    ->defaultNull()
                                    ->info('Application-specific salt combined with kernel.secret for HMAC authentication.')
                                ->end()
                                ->integerNode('signature_ttl')
                                    ->defaultValue(300)
                                    ->min(1)
                                    ->info('Maximum accepted HMAC signature age in seconds.')
                                ->end()
                                ->integerNode('timeout')
                                    ->defaultValue(30)
                                    ->min(1)
                                    ->info('Outbound HTTP request timeout in seconds.')
                                ->end()
                                ->integerNode('max_package_bytes')
                                    ->defaultValue(10485760)
                                    ->min(1)
                                    ->info('Maximum accepted ZIP package size in bytes.')
                                ->end()
                                ->integerNode('max_files')
                                    ->defaultValue(200)
                                    ->min(1)
                                    ->info('Maximum number of YAML files accepted in a package.')
                                ->end()
                                ->scalarNode('basic_auth_username')
                                    ->defaultNull()
                                    ->info('Optional HTTP Basic Auth username for a protected deployed application.')
                                ->end()
                                ->scalarNode('basic_auth_password')
                                    ->defaultNull()
                                    ->info('Optional HTTP Basic Auth password for a protected deployed application.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator->import('../config/services.yaml');

        $container->setParameter('lexio_admin.default_locale', $config['default_locale']);
        $container->setParameter('lexio_admin.locales', $config['locales']);
        $container->setParameter('lexio_admin.admin_route_prefix', $config['admin_route_prefix']);
        $container->setParameter('lexio_admin.listing_items_per_page', $config['listing_items_per_page']);
        $container->setParameter('lexio_admin.deepl_translation_api_key', $config['deepl_translation_api_key']);
        $container->setParameter('lexio_admin.google_translation_api_key', $config['google_translation_api_key']);
        $container->setParameter('lexio_admin.user_entity_class', $config['user_entity_class']);
        $container->setParameter('lexio_admin.front_home_page_route', $config['front_home_page_route']);

        /** @var array{
         *     enabled: bool,
         *     translation_directory: string|null,
         *     synchronization: array{
         *         enabled: bool,
         *         deployed_app_url: string|null,
         *         api_path: string,
         *         auth_salt: string|null,
         *         signature_ttl: int,
         *         timeout: int,
         *         max_package_bytes: int,
         *         max_files: int,
         *         basic_auth_username: string|null,
         *         basic_auth_password: string|null
         *     }
         * } $translationManagement
         */
        $translationManagement = $config['translation_management'];
        $translationDirectory = $translationManagement['translation_directory'];
        if (null === $translationDirectory) {
            $projectDirectory = $container->getParameter('kernel.project_dir');
            if (!is_string($projectDirectory)) {
                throw new \LogicException('The kernel project directory must be a string.');
            }

            $translationDirectory = $projectDirectory . DIRECTORY_SEPARATOR . 'translations';
        }

        $container->setParameter('lexio_admin.translation_management_enabled', $translationManagement['enabled']);
        $container->setParameter('lexio_admin.translation_directory', $translationDirectory);

        $synchronization = $translationManagement['synchronization'];
        $container->setParameter('lexio_admin.translation_synchronization_enabled', $synchronization['enabled']);
        $container->setParameter('lexio_admin.translation_synchronization_deployed_app_url', $synchronization['deployed_app_url']);
        $container->setParameter('lexio_admin.translation_synchronization_api_path', $synchronization['api_path']);
        $container->setParameter('lexio_admin.translation_synchronization_auth_salt', $synchronization['auth_salt']);
        $container->setParameter('lexio_admin.translation_synchronization_signature_ttl', $synchronization['signature_ttl']);
        $container->setParameter('lexio_admin.translation_synchronization_timeout', $synchronization['timeout']);
        $container->setParameter('lexio_admin.translation_synchronization_max_package_bytes', $synchronization['max_package_bytes']);
        $container->setParameter('lexio_admin.translation_synchronization_max_files', $synchronization['max_files']);
        $container->setParameter('lexio_admin.translation_synchronization_basic_auth_username', $synchronization['basic_auth_username']);
        $container->setParameter('lexio_admin.translation_synchronization_basic_auth_password', $synchronization['basic_auth_password']);

        // Conditionally register MakeAdminSectionCommand only when symfony/maker-bundle is installed
        if (class_exists(\Symfony\Bundle\MakerBundle\Str::class)) {
            $configurator->services()
                ->set(Command\Make\MakeAdminSectionCommand::class)
                ->autowire()
                ->autoconfigure()
                ->tag('console.command');
        }
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}


