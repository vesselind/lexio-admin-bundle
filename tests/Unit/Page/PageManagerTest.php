<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Page;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\Page\PageManager;
use Lexio\AdminBundle\Tests\Fixtures\IconPage;
use PHPUnit\Framework\TestCase;

final class PageManagerTest extends TestCase
{
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
