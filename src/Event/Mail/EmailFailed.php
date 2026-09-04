<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Event\Mail;


use Lexio\AdminBundle\Entity\MailTemplate;

final readonly class EmailFailed
{
    public function __construct(
        public MailTemplate $mailTemplate,
        public string $recipientEmail,
        public string $senderEmail,
        public \Throwable $exception,
        public ?string $body = null,
    ) {
    }
}
