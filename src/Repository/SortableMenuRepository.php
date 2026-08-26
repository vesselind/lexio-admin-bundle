<?php

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Entity\SortableMenu;

/**
 * @extends ServiceEntityRepository<SortableMenu>
 *
 * @method SortableMenu|null find($id, $lockMode = null, $lockVersion = null)
 * @method SortableMenu|null findOneBy(array $criteria, array $orderBy = null)
 * @method SortableMenu[]    findAll()
 * @method SortableMenu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SortableMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SortableMenu::class);
    }

    public function save(SortableMenu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SortableMenu $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ?SortableMenu[]
     */
    public function getAll(): ?array
    {
        return $this->createQueryBuilder('sortable_menu')
            ->andWhere('sortable_menu.parent is null')
            ->leftJoin('sortable_menu.children', 'children')
            ->addSelect('children')
            ->orderBy('sortable_menu.positionIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
