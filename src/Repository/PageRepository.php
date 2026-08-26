<?php

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Enum\Pages;
use Lexio\AdminBundle\Filter\PageFilter;
use Lexio\AdminBundle\Normalizer\ContentItemNormalizer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @extends ServiceEntityRepository<Page>
 *
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array $criteria, array $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly SerializerInterface $serializer)
    {
        parent::__construct($registry, Page::class);
    }

    public function filtered(PageFilter $filter): QueryBuilder
    {
        $builder = $this->createQueryBuilder('page');

        if ($filter->title) {
            $builder->andWhere('page.title LIKE :title')
                ->setParameter('title', '%'.$filter->title.'%');
        }

        if ($filter->name) {
            $builder->andWhere('page.name = :name')
                ->setParameter('name', $filter->name);
        }

        return $builder;
    }

    public function remove(Page $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function get(Pages $page): ?Page
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.name = :pageName')
            ->setParameter('pageName', $page->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNormalized(Pages $pages): ?array
    {
        $page = $this->get($pages);

        /** @phpstan-ignore-next-line  */
        return $this->serializer->normalize($page);
    }

    public function searchByTitle(string $title): array
    {
        $title = str_replace(' ', '%', $title);
        return $this->createQueryBuilder('page')
            ->andWhere('page.title LIKE :title')
            ->andWhere('page.name NOT LIKE "%DETAIL%"')
            ->setParameter('title', '%'.$title.'%')
            ->getQuery()
            ->getResult();
    }

}
