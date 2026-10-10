<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Gedmo\Translatable\Entity\Translation;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\EventListener\PageCacheInvalidationListener;
use Lexio\AdminBundle\Page\PageManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\ItemInterface;

final class PageCacheInvalidationListenerTest extends TestCase
{
    #[DataProvider('pageChanges')]
    public function test_page_writes_invalidate_all_locales_after_a_successful_flush(string $schedule, object $entity): void
    {
        $cache = $this->warmCache();
        $listener = new PageCacheInvalidationListener($cache);
        $manager = $this->managerWithChanges($schedule, $entity);

        self::assertTrue($cache->hasItem('test.page.bg'));
        self::assertTrue($cache->hasItem('test.page.en'));

        $listener->onFlush(new OnFlushEventArgs($manager));
        self::assertTrue($cache->hasItem('test.page.bg'));
        $listener->postFlush(new PostFlushEventArgs($manager));

        self::assertFalse($cache->hasItem('test.page.bg'));
        self::assertFalse($cache->hasItem('test.page.en'));
        self::assertTrue($cache->hasItem('test.other'));

        $cache->get('test.page.bg', static function (ItemInterface $item): string {
            $item->tag(PageManager::CACHE_TAG);

            return 'Reloaded';
        });
        $listener->postFlush(new PostFlushEventArgs($manager));
        self::assertTrue($cache->hasItem('test.page.bg'));
    }

    /** @return iterable<string, array{string, object}> */
    public static function pageChanges(): iterable
    {
        foreach (['getScheduledEntityInsertions', 'getScheduledEntityUpdates', 'getScheduledEntityDeletions'] as $schedule) {
            yield $schedule . ' page' => [$schedule, new Page()];
            yield $schedule . ' content' => [$schedule, new ContentItem()];
            yield $schedule . ' page translation' => [$schedule, (new Translation())->setObjectClass(Page::class)];
            yield $schedule . ' content translation' => [$schedule, (new Translation())->setObjectClass(ContentItem::class)];
        }
    }

    public function test_unrelated_writes_leave_the_page_cache_intact(): void
    {
        $cache = $this->warmCache();
        $listener = new PageCacheInvalidationListener($cache);
        $manager = $this->managerWithChanges('getScheduledEntityUpdates', new \stdClass());

        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->postFlush(new PostFlushEventArgs($manager));
        self::assertTrue($cache->hasItem('test.page.bg'));
        self::assertTrue($cache->hasItem('test.page.en'));
    }

    public function test_reset_discards_pending_invalidation_after_an_aborted_flush(): void
    {
        $cache = $this->warmCache();
        $listener = new PageCacheInvalidationListener($cache);
        $manager = $this->managerWithChanges('getScheduledEntityUpdates', new Page());

        $listener->onFlush(new OnFlushEventArgs($manager));
        $listener->reset();
        $listener->postFlush(new PostFlushEventArgs($manager));
        self::assertTrue($cache->hasItem('test.page.bg'));
    }

    public function test_translation_only_updates_are_invalidated_even_when_gedmo_empties_the_update_schedule(): void
    {
        $cache = $this->warmCache();
        $listener = new PageCacheInvalidationListener($cache);

        $updates = [new ContentItem()];
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturnCallback(static function () use (&$updates): array {
            return $updates;
        });
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($unitOfWork);

        $listener->onFlush(new OnFlushEventArgs($manager));
        $updates = []; // Gedmo removes the entity update after writing its translation.
        $listener->postFlush(new PostFlushEventArgs($manager));
        self::assertFalse($cache->hasItem('test.page.en'));
    }

    private function warmCache(): TagAwareAdapter
    {
        $cache = new TagAwareAdapter(new ArrayAdapter());
        foreach (['bg', 'en'] as $locale) {
            $cache->get('test.page.' . $locale, static function (ItemInterface $item) use ($locale): string {
                $item->tag(PageManager::CACHE_TAG);

                return 'Cached ' . $locale;
            });
        }
        $cache->get('test.other', static fn (): string => 'Unrelated data');

        return $cache;
    }

    private function managerWithChanges(string $schedule, object $entity): EntityManagerInterface
    {
        $unitOfWork = $this->createStub(UnitOfWork::class);
        foreach (['getScheduledEntityInsertions', 'getScheduledEntityUpdates', 'getScheduledEntityDeletions'] as $method) {
            $unitOfWork->method($method)->willReturn($method === $schedule ? [$entity] : []);
        }
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')->willReturn($unitOfWork);

        return $manager;
    }
}
