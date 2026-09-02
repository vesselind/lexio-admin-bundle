<?php

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\FooterMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FooterMenu>
 *
 * @method FooterMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method FooterMenu|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method FooterMenu[]    findAll()
 * @method FooterMenu[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class FooterMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FooterMenu::class);
    }

    /** @return array<int, FooterMenu>|null */
    public function getParents(): ?array
    {
        return $this->createQueryBuilder('footerMenu')
            ->andWhere('footerMenu.parent is null')
            ->leftJoin('footerMenu.children', 'children')
            ->addSelect('children')
            ->orderBy('footerMenu.positionIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
