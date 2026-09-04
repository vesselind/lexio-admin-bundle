<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Contract\Notification\NotificationUserFinderInterface;
use Lexio\AdminBundle\Contract\Notification\NotificationUserInterface;
use Lexio\AdminBundle\Contract\Notification\SystemNotificationMailerInterface;
use Lexio\AdminBundle\Message\SendSystemNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendSystemNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ?NotificationUserFinderInterface $userFinder = null,
        private ?SystemNotificationMailerInterface $mailer = null,
    ) {
    }

    public function __invoke(SendSystemNotification $message): void
    {
        /** @var list<int> $usersSent */
        $usersSent = [];

        if ($this->userFinder !== null) {
            foreach ($message->getRoles() as $role) {
                foreach ($this->userFinder->findByRole($role) as $user) {
                    $this->notifyUser($user, $message, $usersSent);
                }
            }
        }

        foreach ($message->getUsers() as $user) {
            $this->notifyUser($user, $message, $usersSent);
        }

        $this->entityManager->flush();
    }

    /** @param list<int> $usersSent */
    private function notifyUser(
        NotificationUserInterface $user,
        SendSystemNotification $message,
        array &$usersSent,
    ): void {
        $userId = $user->getId();
        if ($userId !== null && \in_array($userId, $usersSent, true)) {
            return;
        }

        $this->entityManager->persist(
            new $message->notificationEntity()
                ->setUser($user)
                ->setTitle($message->title)
                ->setLevel($message->level)
                ->setContent($message->content),
        );

        if ($this->mailer !== null && $message->level->isSendingMailRequired()) {
            $this->mailer->sendNotification(
                (string) $user->getEmail(),
                $message->title,
                $message->content,
            );
        }

        if ($userId !== null) {
            $usersSent[] = $userId;
        }
    }
}
