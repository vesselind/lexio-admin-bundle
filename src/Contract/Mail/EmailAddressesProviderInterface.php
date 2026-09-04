<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Mail;

interface EmailAddressesProviderInterface
{
    public function getDefaultSenderEmail(): string;

    public function getDefaultReceiverEmail(): string;
}
