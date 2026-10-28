<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Contract\Api\ApiKeyUserInterface;
use Lexio\AdminBundle\Contract\Api\ApiKeyUserProviderInterface;

/** Default persistence adapter for a configurable host-owned Doctrine user. */
final readonly class DoctrineApiKeyUserProvider implements ApiKeyUserProviderInterface
{
    /** @param class-string<ApiKeyUserInterface> $userEntityClass */
    public function __construct(
        private ManagerRegistry $registry,
        private string $userEntityClass,
        private string $identifierProperty = 'email',
        private string $hashProperty = 'apiKeyHash',
    ) {
    }

    public function findByHash(string $hash): ?ApiKeyUserInterface
    {
        $user = $this->query()
            ->andWhere('api_user.' . $this->hashProperty . ' = :hash')
            ->setParameter('hash', $hash)
            ->getQuery()->getOneOrNullResult();

        return $user instanceof ApiKeyUserInterface ? $user : null;
    }

    public function findByIdentifier(string $identifier): ?ApiKeyUserInterface
    {
        $user = $this->query()
            ->andWhere('api_user.' . $this->identifierProperty . ' = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()->getOneOrNullResult();

        return $user instanceof ApiKeyUserInterface ? $user : null;
    }

    public function findWithoutKey(): iterable
    {
        foreach ($this->query()->andWhere('api_user.' . $this->hashProperty . ' IS NULL')->getQuery()->toIterable() as $user) {
            if ($user instanceof ApiKeyUserInterface) {
                yield $user;
            }
        }
    }

    public function save(ApiKeyUserInterface $user): void
    {
        $this->manager()->persist($user);
    }

    public function flush(): void
    {
        $this->manager()->flush();
    }

    private function query(): QueryBuilder
    {
        return $this->manager()->createQueryBuilder()->select('api_user')->from($this->userEntityClass, 'api_user');
    }

    private function manager(): EntityManagerInterface
    {
        $manager = $this->registry->getManagerForClass($this->userEntityClass);

        if (!$manager instanceof EntityManagerInterface) {
            throw new \LogicException('The configured API user must be managed by Doctrine ORM.');
        }

        return $manager;
    }
}
