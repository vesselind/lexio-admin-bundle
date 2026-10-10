<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\EventListener\PageCacheInvalidationListener;
use Lexio\AdminBundle\LexioAdminBundle;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\Page\PageManager;
use Lexio\AdminBundle\Service\AutoTranslator\EntityAutoTranslator;
use Lexio\AdminBundle\Tests\Fixtures\LocalizedTestPage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class PageCacheConfigurationTest extends TestCase
{
    public function test_page_cache_defaults_are_enabled_with_a_finite_lifetime(): void
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();
        $configuration = $extension->getConfiguration([], $container);
        $processed = (new Processor())->processConfiguration($configuration, [[]]);

        self::assertSame([
            'enabled' => true,
            'pool' => 'lexio_admin.cache.pages',
            'lifetime' => 3600,
        ], $processed['page_cache']);
    }

    public function test_the_bundle_registers_a_tag_aware_filesystem_pool(): void
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());
        $extension = (new LexioAdminBundle())->getContainerExtension();
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);

        $extension->prepend($container);
        $frameworkConfig = $container->getExtensionConfig('framework');

        self::assertSame([
            'adapter' => 'cache.adapter.filesystem',
            'tags' => true,
        ], $frameworkConfig[0]['cache']['pools']['lexio_admin.cache.pages']);
    }

    public function test_page_cache_rejects_a_zero_lifetime(): void
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();
        $configuration = $extension->getConfiguration([], $container);

        $this->expectException(InvalidConfigurationException::class);
        (new Processor())->processConfiguration($configuration, [['page_cache' => ['lifetime' => 0]]]);
    }

    #[DataProvider('cacheConfigurations')]
    public function test_page_services_compile_and_respect_cache_configuration(array $config, bool $enabled, string $pool): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 3));
        $extension = (new LexioAdminBundle())->getContainerExtension();
        $extension->load([$config], $container);

        self::assertSame($enabled, $container->hasDefinition(PageCacheInvalidationListener::class));
        if ($enabled) {
            $tags = $container->getDefinition(PageCacheInvalidationListener::class)->getTag('doctrine.event_listener');
            self::assertSame('onFlush', $tags[0]['event']);
            self::assertSame(500, $tags[0]['priority']);
            self::assertSame('postFlush', $tags[1]['event']);
        }

        // Compile the bundle's actual page definitions with test doubles for host dependencies.
        $pageServices = ['service_container', PageManager::class, PageCacheInvalidationListener::class];
        foreach (array_keys($container->getDefinitions()) as $id) {
            if (!in_array($id, $pageServices, true)) {
                $container->removeDefinition($id);
            }
        }
        foreach (array_keys($container->getAliases()) as $id) {
            if (!in_array($id, [PageAdministrationInterface::class, EntityAutoTranslatorInterface::class], true)) {
                $container->removeAlias($id);
            }
        }
        $container->getAlias(PageAdministrationInterface::class)->setPublic(true);
        $container->register('doctrine.orm.entity_manager', EntityManagerInterface::class)->setSynthetic(true)->setPublic(true);
        $container->setAlias(EntityManagerInterface::class, 'doctrine.orm.entity_manager');
        $container->register(EntityAutoTranslator::class, EntityAutoTranslator::class)->setSynthetic(true)->setPublic(true);
        $container->register($pool, TagAwareAdapter::class)->addArgument(new Definition(ArrayAdapter::class));
        $container->compile();

        $page = (new Page())->setName(LocalizedTestPage::class);
        $page->addContentItem((new ContentItem())->setName('title')
            ->setType(ContentItemTypes::TEXT_INPUT)->setValue('Database title'));
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly($enabled ? 1 : 2))->method('findOneBy')->willReturn($page);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects(self::exactly($enabled ? 2 : 4))->method('refresh');
        $container->set('doctrine.orm.entity_manager', $manager);
        $container->set(EntityAutoTranslator::class, $this->createStub(EntityAutoTranslatorInterface::class));

        $pageManager = $container->get(PageAdministrationInterface::class);
        self::assertInstanceOf(PageManager::class, $pageManager);
        self::assertSame('Database title', $pageManager->getPageObject(LocalizedTestPage::class)?->getTitle());
        self::assertSame('Database title', $pageManager->getPageObject(LocalizedTestPage::class)?->getTitle());
    }

    /** @return iterable<string, array{array<string, mixed>, bool, string}> */
    public static function cacheConfigurations(): iterable
    {
        yield 'default filesystem pool' => [[], true, 'lexio_admin.cache.pages'];
        yield 'disabled' => [['page_cache' => ['enabled' => false]], false, 'lexio_admin.cache.pages'];
        yield 'custom pool' => [['page_cache' => ['pool' => 'app.cache.pages', 'lifetime' => 120]], true, 'app.cache.pages'];
    }
}
