<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Event\AdminLifecycle;

/** Dispatched immediately after a new entity is persisted and flushed. */
final class AfterEntityPersisted extends AdminLifecycleEvent
{
}

