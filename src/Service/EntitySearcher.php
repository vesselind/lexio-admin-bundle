<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Lexio\AdminBundle\Contract\SearchableEntityInterface;

class EntitySearcher
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Searches the given entity class using its declared searchable fields.
     *
     * The entity class must implement {@see SearchableEntityInterface}.
     * @param class-string<SearchableEntityInterface> $entityClass
     */
    public function search(
        string  $entityClass,
        ?string $query          = null,
        ?string $sortByProperty = null,
        ?string $order          = null,
        ?int    $limit          = null,
    ): ?Query {
        $entityInstance = new $entityClass();

        /** @phpstan-ignore-next-line  */
        if (!$entityInstance instanceof SearchableEntityInterface) {
            throw new \RuntimeException(sprintf(
                'The entity %s must implement %s to be used with EntitySearcher.',
                $entityClass,
                SearchableEntityInterface::class,
            ));
        }

        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $fields   = array_values($entityInstance->getSearchableParameters()->searchableFields);

        if (empty($fields)) {
            throw new \RuntimeException(sprintf(
                'The entity %s must declare at least one searchable field.',
                $entityClass,
            ));
        }

        $qb = $this->searchByText($entityClass, $metadata, $query, $fields);

        if ($sortByProperty && in_array($sortByProperty, $fields, true)) {
            $qb->orderBy('e.' . $sortByProperty, $order ?: 'ASC');
        } else {
            $qb->orderBy('e.id', 'DESC');
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery();
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param list<string> $fields
     */
    private function searchByText(
        string        $entityClass,
        ClassMetadata $metadata,
        ?string       $query,
        array         $fields,
    ): QueryBuilder {
        /** @var class-string $entityClass */
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from($entityClass, 'e');

        if (empty($query)) {
            return $qb;
        }

        $orX = $qb->expr()->orX();

        foreach ($fields as $field) {
            if (in_array($metadata->getTypeOfField($field), ['string', 'text'], true)) {
                $orX->add($qb->expr()->like('e.' . $field, ':query'));
            }
        }

        if ($orX->count() === 0) {
            return $qb;
        }

        return $qb->where($orX)->setParameter('query', '%' . $query . '%');
    }
}

