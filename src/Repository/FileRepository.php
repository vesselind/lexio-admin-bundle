<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Filter\FileFilter;

/**
 * @template T of FileEntityInterface
 * @extends ServiceEntityRepository<T>
 */
abstract class FileRepository extends ServiceEntityRepository
{
    public function filtered(FileFilter $filters): QueryBuilder
    {
        $builder = $this->createQueryBuilder('file')
            ->orderBy('file.createdAt', 'DESC')->addOrderBy('file.id', 'DESC');

        foreach (['name', 'mime', 'originalName'] as $field) {
            if ($filters->$field) {
                $builder->andWhere('file.' . $field . ' LIKE :' . $field)
                    ->setParameter($field, '%' . $filters->$field . '%');
            }
        }

        if ($filters->size !== null) {
            $builder->andWhere('file.size = :size')->setParameter('size', $filters->size);
        }

        return $builder;
    }
}
