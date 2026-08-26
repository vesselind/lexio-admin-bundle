<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber;

use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Event\AdminLifecycle\AfterEntityPersisted;
use Lexio\AdminBundle\Event\AdminLifecycle\AfterEntityUpdated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class AdminLifecycleSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityAutoTranslatorInterface $autoTranslator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersisted::class => 'onAfterEntityPersisted',
            AfterEntityUpdated::class => 'onAfterEntityUpdated',
        ];
    }

    public function onAfterEntityPersisted(AfterEntityPersisted $event): void
    {
        $this->autoTranslator->translateFields($event->getEntity(), $event->getLocale());
    }

    public function onAfterEntityUpdated(AfterEntityUpdated $event): void
    {
        $this->autoTranslator->translateFields($event->getEntity(), $event->getLocale());
    }
}
