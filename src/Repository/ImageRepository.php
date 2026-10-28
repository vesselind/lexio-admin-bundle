<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Filter\ImageFilter;

/**
 * @template T of ImageEntityInterface
 * @extends ServiceEntityRepository<T>
 */
abstract class ImageRepository extends ServiceEntityRepository
{
    public function filtered(ImageFilter $filter): QueryBuilder
    {
        $builder = $this->createQueryBuilder('image')->orderBy('image.id', 'DESC');

        if ($filter->name !== null && $filter->name !== '') {
            $builder->andWhere('image.name LIKE :filename')
                ->setParameter('filename', '%' . str_replace(' ', '%', $filter->name) . '%');
        }

        return $builder;
    }
}
