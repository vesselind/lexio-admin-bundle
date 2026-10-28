<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Api;

use Symfony\Component\Security\Core\User\UserInterface;

/** Optional capability for host users that own a bearer API credential. */
interface ApiKeyUserInterface extends UserInterface
{
    public function getApiKeyHash(): ?string;

    public function setApiKeyHash(?string $apiKeyHash): static;
}
