<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Repository;

use Carbon\Carbon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Entity\EmailDelivery;
use Lexio\AdminBundle\Filter\EmailDeliveryFilter;

/** @extends ServiceEntityRepository<EmailDelivery> */
final class EmailDeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailDelivery::class);
    }


    public function countRecent(): int
    {
        return (int) ($this->createQueryBuilder('email_delivery')
            ->select('COUNT(email_delivery.id)')
            ->where('email_delivery.createdAt >= :referenceDate')
            ->setParameter('referenceDate', new \DateTimeImmutable('-1 days'))
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

    }

    public function deleteOlderThan(int $months): int
    {
        $olderThanDate = Carbon::now()->subMonths($months)->toDateTimeImmutable();

        return (int) $this->createQueryBuilder('email_delivery')
            ->delete()
            ->andWhere('email_delivery.createdAt < :date')
            ->setParameter('date', $olderThanDate)
            ->getQuery()
            ->execute();
    }

    public function filtered(EmailDeliveryFilter $filter): QueryBuilder
    {
        $builder = $this->createQueryBuilder('email_delivery')
            ->orderBy('email_delivery.id', 'DESC');

        if ($filter->recipientEmail) {
            $builder->andWhere('email_delivery.recipientEmail LIKE :recipientEmail')
                ->setParameter('recipientEmail', '%' . $filter->recipientEmail . '%');
        }

        if ($filter->senderEmail) {
            $builder->andWhere('email_delivery.senderEmail LIKE :senderEmail')
                ->setParameter('senderEmail', '%' . $filter->senderEmail . '%');
        }

        if ($filter->error) {
            $builder->andWhere('email_delivery.error LIKE :error')
                ->setParameter('error', '%' . $filter->error . '%');
        }

        return $builder;
    }
}
