<?php

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\Entity\ContentItem;

/**
 * @extends ServiceEntityRepository<ContentItem>
 *
 * @method ContentItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentItem[]    findAll()
 * @method ContentItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentItem::class);
    }

}
