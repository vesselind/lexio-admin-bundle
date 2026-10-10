<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Page;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Page\BasePage;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\Page\PageManager;
use Lexio\AdminBundle\Tests\Fixtures\IconPage;
use Lexio\AdminBundle\Tests\Fixtures\LocalizedTestPage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

final class PageManagerTest extends TestCase
{
    public function test_it_reads_pages_with_the_original_three_constructor_arguments(): void
    {
        $page = (new Page())->setName(LocalizedTestPage::class);
        $page->addContentItem((new ContentItem())->setName('title')
            ->setType(ContentItemTypes::TEXT_INPUT)->setValue('Database title'));
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findOneBy')->willReturn($page);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects(self::exactly(2))->method('refresh');

        $pageManager = new PageManager($manager, $this->createStub(EntityAutoTranslatorInterface::class), 'bg');
        self::assertSame('Database title', $pageManager->getPageObject(LocalizedTestPage::class)?->getTitle());
    }

    public function test_cached_reads_skip_page_and_content_queries_and_restore_managed_images(): void
    {
        $image = $this->createStub(ImageEntityInterface::class);
        $image->method('getId')->willReturn(99);
        $page = (new Page())->setName(LocalizedTestPage::class);
        (new \ReflectionProperty(Page::class, 'id'))->setValue($page, 42);
        $page->addContentItem((new ContentItem())->setName('title')
            ->setType(ContentItemTypes::TEXT_INPUT)->setValue('Database title'));
        $page->addContentItem((new ContentItem())->setName('seoDescription')
            ->setType(ContentItemTypes::TEXTAREA)->setValue(null));
        $page->addContentItem((new ContentItem())->setName('coverImage')
            ->setType(ContentItemTypes::IMAGE)->setImage($image));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findOneBy')
            ->with(['name' => LocalizedTestPage::class])->willReturn($page);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::once())->method('getRepository')->with(Page::class)->willReturn($repository);
        $manager->expects(self::exactly(4))->method('refresh')
            ->willReturnCallback(static function (object $entity): void {
                self::assertSame('bg', (new \ReflectionProperty($entity::class, 'locale'))->getValue($entity));
            });
        $manager->expects(self::once())->method('getClassMetadata')
            ->with($image::class)->willReturn(new ClassMetadata($image::class));
        $manager->expects(self::once())->method('getReference')->with($image::class, 99)->willReturn($image);

        $cache = new TagAwareAdapter(new ArrayAdapter());
        $autoTranslator = $this->createStub(EntityAutoTranslatorInterface::class);
        $first = (new PageManager($manager, $autoTranslator, 'bg', $cache))
            ->getPageObject(LocalizedTestPage::class);

        self::assertInstanceOf(LocalizedTestPage::class, $first);
        self::assertSame(42, $first->getId());
        self::assertSame('Database title', $first->getTitle());
        self::assertNull($first->getSeoDescription());
        self::assertSame($image, $first->getCoverImage());
        $first->setTitle('Caller modification');

        $managedImage = $this->createStub(ImageEntityInterface::class);
        $nextManager = $this->createMock(EntityManagerInterface::class);
        $nextManager->expects(self::never())->method('getRepository');
        $nextManager->expects(self::never())->method('refresh');
        $nextManager->expects(self::never())->method('getClassMetadata');
        $nextManager->expects(self::once())->method('getReference')->with($image::class, 99)->willReturn($managedImage);
        $second = (new PageManager($nextManager, $autoTranslator, 'bg', $cache))
            ->getPageObject(LocalizedTestPage::class, 'bg');

        self::assertInstanceOf(LocalizedTestPage::class, $second);
        self::assertNotSame($first, $second);
        self::assertSame('Database title', $second->getTitle());
        self::assertSame(42, $second->getId());
        self::assertNull($second->getSeoDescription());
        self::assertSame($managedImage, $second->getCoverImage());
    }

    public function test_the_cache_separates_page_classes_and_locales(): void
    {
        $pages = [];
        foreach ([LocalizedTestPage::class, BasePage::class] as $pageClass) {
            $pages[$pageClass] = (new Page())->setName($pageClass);
            $pages[$pageClass]->addContentItem((new ContentItem())->setName('title')->setType(ContentItemTypes::TEXT_INPUT));
        }
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(4))->method('findOneBy')
            ->willReturnCallback(static fn (array $criteria): Page => $pages[$criteria['name']]);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $manager->expects(self::exactly(8))->method('refresh')->willReturnCallback(static function (object $entity): void {
            if ($entity instanceof ContentItem) {
                $locale = (new \ReflectionProperty(ContentItem::class, 'locale'))->getValue($entity);
                $entity->setValue($entity->getPage()?->getName() . ' ' . $locale);
            }
        });
        $pageManager = new PageManager($manager, $this->createStub(EntityAutoTranslatorInterface::class), 'bg',
            new TagAwareAdapter(new ArrayAdapter()));

        for ($read = 0; $read < 2; ++$read) {
            foreach (array_keys($pages) as $pageClass) {
                foreach (['bg', 'en'] as $locale) {
                    self::assertSame($pageClass . ' ' . $locale, $pageManager->getPageObject($pageClass, $locale)?->getTitle());
                }
            }
        }
    }

    public function test_missing_pages_are_cached_until_invalidation(): void
    {
        $page = (new Page())->setName(LocalizedTestPage::class);
        $page->addContentItem((new ContentItem())->setName('title')
            ->setType(ContentItemTypes::TEXT_INPUT)->setValue('Created page'));
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))->method('findOneBy')->willReturnOnConsecutiveCalls(null, $page);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $cache = new TagAwareAdapter(new ArrayAdapter());
        $pageManager = new PageManager($manager, $this->createStub(EntityAutoTranslatorInterface::class), 'bg', $cache);

        self::assertNull($pageManager->getPageObject(LocalizedTestPage::class));
        self::assertNull($pageManager->getPageObject(LocalizedTestPage::class));
        $cache->invalidateTags([PageManager::CACHE_TAG]);
        self::assertSame('Created page', $pageManager->getPageObject(LocalizedTestPage::class)?->getTitle());
    }

    public function test_the_cache_applies_the_configured_lifetime(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn((new Page())->setName(LocalizedTestPage::class));
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $items = new ArrayAdapter();
        $pageManager = new PageManager($manager, $this->createStub(EntityAutoTranslatorInterface::class), 'bg',
            new TagAwareAdapter($items), 120);

        $before = microtime(true);
        $pageManager->getPageObject(LocalizedTestPage::class);
        $after = microtime(true);
        $contentKeys = array_filter(array_keys($items->getValues()), static fn (string $key): bool => str_starts_with($key, 'page.'));
        self::assertCount(1, $contentKeys);
        $metadata = $items->getItem(array_values($contentKeys)[0])->getMetadata();
        self::assertArrayHasKey('expiry', $metadata);
        self::assertGreaterThanOrEqual($before + 119, $metadata['expiry']);
        self::assertLessThanOrEqual($after + 121, $metadata['expiry']);
    }

    public function test_database_failures_are_not_cached(): void
    {
        $calls = 0;
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::exactly(2))->method('findOneBy')->willReturnCallback(static function () use (&$calls): Page {
            if (++$calls === 1) {
                throw new \RuntimeException('Database unavailable');
            }

            return (new Page())->setName(LocalizedTestPage::class);
        });
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getRepository')->willReturn($repository);
        $pageManager = new PageManager($manager, $this->createStub(EntityAutoTranslatorInterface::class), 'bg',
            new TagAwareAdapter(new ArrayAdapter()));

        try {
            $pageManager->getPageObject(LocalizedTestPage::class);
            self::fail('The database exception must be propagated.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Database unavailable', $exception->getMessage());
        }
        self::assertInstanceOf(LocalizedTestPage::class, $pageManager->getPageObject(LocalizedTestPage::class));
    }

    public function test_it_stores_non_translatable_icons_as_values_without_translation(): void
    {
        $pageRepository = $this->createMock(EntityRepository::class);
        $pageRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => IconPage::class])
            ->willReturn(null);

        $contentItemRepository = $this->createStub(EntityRepository::class);
        $contentItemRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persistedEntities = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturnMap([
                [Page::class, $pageRepository],
                [ContentItem::class, $contentItemRepository],
            ]);
        $entityManager
            ->expects($this->atLeastOnce())
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persistedEntities): void {
                $persistedEntities[] = $entity;
            });
        $entityManager->expects($this->atLeastOnce())->method('flush');

        $translatedFieldNames = [];
        $autoTranslator = $this->createStub(EntityAutoTranslatorInterface::class);
        $autoTranslator
            ->method('translateField')
            ->willReturnCallback(static function (object $entity) use (&$translatedFieldNames): bool {
                if ($entity instanceof ContentItem) {
                    $translatedFieldNames[] = $entity->getName();
                }

                return true;
            });

        (new PageManager($entityManager, $autoTranslator, 'bg'))
            ->createOrUpdatePage(new IconPage());

        $contentItem = null;
        foreach ($persistedEntities as $entity) {
            if ($entity instanceof ContentItem && $entity->getName() === 'icon') {
                $contentItem = $entity;
                break;
            }
        }

        self::assertInstanceOf(ContentItem::class, $contentItem);
        self::assertSame(ContentItemTypes::ICON, $contentItem->getType());
        self::assertSame('mdi:home', $contentItem->getValue());
        self::assertNull($contentItem->getImage());
        self::assertCount(4, $translatedFieldNames);
        self::assertNotContains('icon', $translatedFieldNames);
    }

    public function test_it_updates_translatable_content_but_skips_non_translatable_fields_in_other_locales(): void
    {
        $pageEntity = new Page();
        $pageRepository = $this->createMock(EntityRepository::class);
        $pageRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => IconPage::class])
            ->willReturn($pageEntity);

        $contentItemRepository = $this->createStub(EntityRepository::class);
        $contentItemRepository
            ->method('findOneBy')
            ->willReturn(null);

        $persistedEntities = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getRepository')
            ->willReturnMap([
                [Page::class, $pageRepository],
                [ContentItem::class, $contentItemRepository],
            ]);
        $entityManager
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persistedEntities): void {
                $persistedEntities[] = $entity;
            });
        $entityManager->expects($this->atLeastOnce())->method('refresh');
        $entityManager->expects($this->atLeastOnce())->method('flush');

        $autoTranslator = $this->createMock(EntityAutoTranslatorInterface::class);
        $autoTranslator->expects($this->never())->method('translateField');

        $page = (new IconPage())->setTitle('English title');
        (new PageManager($entityManager, $autoTranslator, 'bg'))
            ->createOrUpdatePage($page, 'en');

        $icon = null;
        $title = null;
        foreach ($persistedEntities as $entity) {
            if (!$entity instanceof ContentItem) {
                continue;
            }

            match ($entity->getName()) {
                'icon' => $icon = $entity,
                'title' => $title = $entity,
                default => null,
            };
        }

        self::assertInstanceOf(ContentItem::class, $icon);
        self::assertNull($icon->getValue());
        self::assertNull($icon->getImage());
        self::assertInstanceOf(ContentItem::class, $title);
        self::assertSame('English title', $title->getValue());
    }
}
