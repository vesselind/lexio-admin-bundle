<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures\Api;

use Lexio\AdminBundle\Contract\Api\ApiKeyUserInterface;

final class PortableUser implements ApiKeyUserInterface
{
    private ?string $hash = null;

    public function __construct(private readonly string $identifier = 'operator@example.test')
    {
    }

    public function getUserIdentifier(): string { return $this->identifier; }
    public function getRoles(): array { return ['ROLE_USER']; }
    public function eraseCredentials(): void {}
    public function getApiKeyHash(): ?string { return $this->hash; }
    public function setApiKeyHash(?string $apiKeyHash): static { $this->hash = $apiKeyHash; return $this; }
}
