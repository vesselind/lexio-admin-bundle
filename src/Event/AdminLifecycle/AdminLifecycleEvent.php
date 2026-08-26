<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Event\AdminLifecycle;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Base event for the admin entity lifecycle.
 */
abstract class AdminLifecycleEvent extends Event
{
    public function __construct(
        private readonly object $entity,
        private readonly string $locale,
    ) {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}

