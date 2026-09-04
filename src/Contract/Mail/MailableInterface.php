<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Mail;

interface MailableInterface
{
    public function getSnakeClassName(): ?string;

    public function templatePath(): string;

    public function subject(): string;

    public function subjectOverride(): ?string;
    /** @return array<string, mixed> */
    public function toArray(): array;
}
