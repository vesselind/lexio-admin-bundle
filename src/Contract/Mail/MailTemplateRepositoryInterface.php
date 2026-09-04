<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Mail;


use Lexio\AdminBundle\Entity\MailTemplate;

interface MailTemplateRepositoryInterface
{
    public function findByName(string $name): ?MailTemplate;
}
