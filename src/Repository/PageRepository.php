<?php

namespace Lexio\AdminBundle\Repository;

use Lexio\AdminBundle\Entity\Page;
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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @extends ServiceEntityRepository<Page>
 *
 * @method Page|null find($id, $lockMode = null, $lockVersion = null)
 * @method Page|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Page[]    findAll()
 * @method Page[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class PageRepository extends ServiceEntityRepository
{
    use TranslatableHints;

    public function __construct(ManagerRegistry $registry, private readonly NormalizerInterface $serializer)
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

    public function get(string $pageName): ?Page
    {
        return $this->createQueryBuilder('page')
            ->andWhere('page.name = :pageName')
            ->setParameter('pageName', $pageName)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** @return array<string, mixed>|null */
    public function getNormalized(string $pageName): ?array
    {
        $page = $this->get($pageName);

        if ($page === null) {
            return null;
        }

        $normalized = $this->serializer->normalize($page);
        if (!is_array($normalized)) {
            throw new \UnexpectedValueException('A page must normalize to an array.');
        }

        return $normalized;
    }

    /** @return array<int, Page> */
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
