<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Api;

interface ApiKeyUserProviderInterface
{
    public function findByHash(string $hash): ?ApiKeyUserInterface;

    public function findByIdentifier(string $identifier): ?ApiKeyUserInterface;

    /** @return iterable<ApiKeyUserInterface> */
    public function findWithoutKey(): iterable;

    /** Schedules persistence; flush() commits the pending credential changes. */
    public function save(ApiKeyUserInterface $user): void;

    public function flush(): void;
}
