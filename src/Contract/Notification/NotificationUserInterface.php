<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Notification;

/**
 * Must be implemented by (or applied to) the application's User entity.
 *
 * Register the mapping in lexio_admin.yaml:
 *   lexio_admin:
 *     user_entity_class: App\Entity\User
 *
 * The bundle will automatically wire ResolveTargetEntityListener so that
 * SystemNotification.user references this interface, resolved to your User entity.
 */
interface NotificationUserInterface
{
    public function getId(): ?int;

    public function getEmail(): ?string;

    /** @return list<string> */
    public function getRoles(): array;

}

