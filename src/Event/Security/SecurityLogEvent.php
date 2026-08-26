<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Event\Security;

abstract class SecurityLogEvent
{
    public function __construct(
        public readonly ?string $actingUser,
        public readonly ?string $affectedUser,
        public readonly ?bool $success = true,
    ) {
    }
}
