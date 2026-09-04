<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Notification;

use Lexio\AdminBundle\Contract\Notification\NotificationUserInterface;

/**
 * Provides users for a given Symfony security role string.
 */
interface NotificationUserFinderInterface
{
    /**
     * @return list<NotificationUserInterface>
     */
    public function findByRole(string $role): array;
}
