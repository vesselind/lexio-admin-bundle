<?php

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\FooterMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FooterMenu>
 *
 * @method FooterMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method FooterMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method FooterMenu[]    findAll()
 * @method FooterMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FooterMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FooterMenu::class);
    }

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
