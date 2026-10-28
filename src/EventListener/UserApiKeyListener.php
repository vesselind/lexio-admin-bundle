<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventListener;

use Lexio\AdminBundle\Api\Service\ApiKeyIssuer;
use Lexio\AdminBundle\Event\Security\UserHasRegistered;

/**
 * Assigns an API key to every user who completes the registration flow.
 */
final readonly class UserApiKeyListener
{
    public function __construct(
        private ApiKeyIssuer $issuer,
    ) {
    }

    public function __invoke(UserHasRegistered $event): void
    {
        if ($event->affectedUser === null) {
            return;
        }

        $this->issuer->ensureKey($event->affectedUser);
    }
}
