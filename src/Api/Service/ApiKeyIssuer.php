<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Lexio\AdminBundle\Contract\Api\ApiKeyUserProviderInterface;

final readonly class ApiKeyIssuer
{
    public function __construct(
        private ApiKeyUserProviderInterface $users,
        private ApiKeyGenerator $generator,
    ) {
    }

    public function issue(string $identifier): ?string
    {
        $user = $this->users->findByIdentifier($identifier);

        if ($user === null) {
            return null;
        }

        $key = $this->generator->assign($user);
        $this->users->save($user);
        $this->users->flush();

        return $key;
    }

    public function ensureKey(string $identifier): void
    {
        $user = $this->users->findByIdentifier($identifier);

        if ($user === null || $user->getApiKeyHash() !== null) {
            return;
        }

        $this->generator->assign($user);
        $this->users->save($user);
        $this->users->flush();
    }

    public function backfill(): int
    {
        $count = 0;

        foreach ($this->users->findWithoutKey() as $user) {
            $this->generator->assign($user);
            $this->users->save($user);
            ++$count;

            if ($count % 100 === 0) {
                $this->users->flush();
            }
        }

        if ($count > 0) {
            $this->users->flush();
        }

        return $count;
    }
}
