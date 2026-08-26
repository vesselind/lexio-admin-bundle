<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract;

/**
 * Implement this interface in your application and register it as a service
 * to power the Admin:NotificationBell Twig component.
 *
 * Example:
 *   class MyNotificationProvider implements NotificationBellProviderInterface { ... }
 */
interface NotificationBellProviderInterface
{
    public function getUnreadCount(): int;

    /**
     * Returns the latest notifications to display in the bell dropdown.
     *
     * Each element should expose: title, content, createdAt, viewedAt, level (with a value property).
     *
     * @return array<mixed>
     */
    public function getLatestNotifications(): array;
}

