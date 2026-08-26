<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\SecurityLog;
use Lexio\AdminBundle\Enum\SecurityEvents;
use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityLog>
 *
 * @method SecurityLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method SecurityLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method SecurityLog[]    findAll()
 * @method SecurityLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SecurityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityLog::class);
    }

    /**
     * @param int $months
     * @return SecurityLog[]|null
     */
    /**
     * Returns top active users ranked by login event count.
     * Each element: ['email' => string, 'loginCount' => int]
     *
     * @return array<int, array{email: string, loginCount: int}>
     */
    public function getTopActiveUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.actingUser AS email, COUNT(a.id) AS loginCount')
            ->andWhere('a.actingUser IS NOT NULL')
            ->andWhere("a.type IN (:types)")
            ->setParameter('types', [
                SecurityEvents::LOGIN->value,
                SecurityEvents::INTERACTIVE_LOGIN->value,
            ])
            ->groupBy('a.actingUser')
            ->orderBy('loginCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function getOlderThan(int $months): ?array
    {
        $olderThanDate = Carbon::now()->subMonths($months)->toDateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->andWhere('a.createdAt < :date')
            ->setParameter('date', $olderThanDate)
            ->getQuery()
            ->getResult();
    }
}
