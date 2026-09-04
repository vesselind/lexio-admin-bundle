<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Mail;

interface MailSenderInterface
{
    public function send(MailableInterface $mailable, ?string $toEmail = null, ?string $fromEmail = null): void;
}
