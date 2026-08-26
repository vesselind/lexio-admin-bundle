<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Lexio\AdminBundle\Contract\NotificationBellProviderInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Renders the notification bell dropdown in the admin navbar.
 *
 * Requires a service implementing NotificationBellProviderInterface.
 * Register it in your application services:
 *
 *   class MyNotificationProvider implements NotificationBellProviderInterface { ... }
 */
#[AsTwigComponent(name: 'Admin:NotificationBell', template: '@LexioAdmin/components/Admin/NotificationBell.html.twig')]
final readonly class NotificationBell
{
    public function __construct(
        private NotificationBellProviderInterface $provider,
    ) {
    }

    public function getUnreadCount(): int
    {
        return $this->provider->getUnreadCount();
    }

    /**
     * @return array<mixed>
     */
    public function getLatestNotifications(): array
    {
        return $this->provider->getLatestNotifications();
    }
}

