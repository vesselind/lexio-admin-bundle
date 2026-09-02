<?php

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\HeaderMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HeaderMenu>
 *
 * @method HeaderMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method HeaderMenu|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method HeaderMenu[]    findAll()
 * @method HeaderMenu[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class HeaderMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HeaderMenu::class);
    }

    /** @return array<int, HeaderMenu>|null */
    public function getParents(): ?array
    {
        return $this->createQueryBuilder('headerMenu')
            ->andWhere('headerMenu.parent is null')
            ->leftJoin('headerMenu.children', 'children')
            ->addSelect('children')
            ->orderBy('headerMenu.positionIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
