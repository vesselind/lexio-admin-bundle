<?php

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Entity\Seed;

/**
 * @extends ServiceEntityRepository<Seed>
 *
 * @method Seed|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seed|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seed[]    findAll()
 * @method Seed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seed::class);
    }
}
