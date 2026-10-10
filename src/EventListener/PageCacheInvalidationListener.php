<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Page\PageManager;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Service\ResetInterface;

final class PageCacheInvalidationListener implements ResetInterface
{
    private bool $invalidateAfterFlush = false;

    public function __construct(private readonly TagAwareCacheInterface $cache)
    {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $this->reset();
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        foreach ([
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ] as $entity) {
            if ($entity instanceof Page || $entity instanceof ContentItem
                || ($entity instanceof AbstractTranslation
                    && in_array($entity->getObjectClass(), [Page::class, ContentItem::class], true))
            ) {
                $this->invalidateAfterFlush = true;

                return;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->invalidateAfterFlush) {
            return;
        }

        $this->reset();
        $this->cache->invalidateTags([PageManager::CACHE_TAG]);
    }

    public function reset(): void
    {
        $this->invalidateAfterFlush = false;
    }
}
