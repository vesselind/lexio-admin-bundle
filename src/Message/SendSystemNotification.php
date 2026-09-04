<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Message;

use Lexio\AdminBundle\Contract\Notification\NotificationEntityInterface;
use Lexio\AdminBundle\Contract\Notification\NotificationUserInterface;
use Lexio\AdminBundle\Enum\NotificationLevel;

/**
 * Persists a system notification for one or more users and optionally sends e-mail.
 *
 * @param list<mixed> $rolesOrUsers
 */
final readonly class SendSystemNotification
{
    /**
     * @param list<mixed> $rolesOrUsers
     *
     * @throws \InvalidArgumentException when targets are empty or invalid
     */
    public function __construct(
        public string $title,
        public string $content,
        public NotificationLevel $level,
        /** @var list<mixed> */
        public array $rolesOrUsers,
        /** @var class-string<NotificationEntityInterface> */
        public string $notificationEntity,
    ) {
        if ($rolesOrUsers === []) {
            throw new \InvalidArgumentException('At least one user or role must be specified for a notification.');
        }

        foreach ($rolesOrUsers as $item) {
            if ($item instanceof NotificationUserInterface || \is_string($item)) {
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                'rolesOrUsers must contain only NotificationUserInterface instances or role strings, %s given.',
                get_debug_type($item),
            ));
        }
    }

    /** @return list<NotificationUserInterface> */
    public function getUsers(): array
    {
        $users = [];

        foreach ($this->rolesOrUsers as $item) {
            if ($item instanceof NotificationUserInterface) {
                $users[] = $item;
            }
        }

        return $users;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = [];

        foreach ($this->rolesOrUsers as $item) {
            if (\is_string($item)) {
                $roles[] = $item;
            }
        }

        return $roles;
    }
}
