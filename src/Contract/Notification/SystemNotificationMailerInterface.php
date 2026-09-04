<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Notification;

interface SystemNotificationMailerInterface
{
    public function sendNotification(string $email, string $title, string $content): void;
}
