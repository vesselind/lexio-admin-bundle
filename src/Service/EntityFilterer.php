<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Lexio\AdminBundle\Contract\Filter\BaseFilterInterface;

/**
 * Centralised search/filter service for admin listings.
 *
 * Resolves the correct repository, applies the typed filter DTO (if provided),
 * then adds full-text search and sorting on top.
 *
 * Convention: if the repository exposes a `filtered(BaseFilter $filter): QueryBuilder`
 * method it will be called to apply structured filters. Otherwise a plain
 * `findAll`-style query builder is used.
 */
class EntityFilterer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    /**
     * Build a query for paginated listing.
     *
     * @param class-string       $entityFqcn  Entity class name.
     * @param string             $q           Free-text search string (empty = no FTS).
     * @param string|null        $sort        Property name to sort by.
     * @param string|null        $order       'asc' or 'desc'.
     * @param BaseFilterInterface|null    $filter      Optional typed filter DTO.
     */
    public function search(
        string               $entityFqcn,
        string               $q = '',
        ?string              $sort = null,
        ?string              $order = null,
        ?BaseFilterInterface $filter = null,
    ): QueryBuilder {
        $repository = $this->em->getRepository($entityFqcn);
        $alias      = $this->buildAlias($entityFqcn);
        
        // Use typed filtered() method when the repository provides one.
        if ($filter !== null && method_exists($repository, 'filtered')) {
            $qb = $repository->filtered($filter);
        } else {
            $qb = $repository->createQueryBuilder($alias);
        }

        // Picked up the actual root alias from the QueryBuilder (filtered() may use a different one).
        $rootAliases = $qb->getRootAliases();
        $alias = $rootAliases[0] ?? $alias;

        // Full-text keyword search: look for string properties on the entity.
        if ($q !== '') {
            $this->applyKeywordSearch($qb, $entityFqcn, $alias, $q);
        }

        // Sorting.
        if ($sort !== null && $this->isSafeFieldName($sort)) {
            $direction = strtolower($order ?? 'desc') === 'desc' ? 'DESC' : 'ASC';
            $qb->orderBy($alias . '.' . $sort, $direction);
        }

        return $qb;
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function buildAlias(string $entityFqcn): string
    {
        $parts = explode('\\', $entityFqcn);
        return strtolower(substr(end($parts), 0, 1));
    }

    private function applyKeywordSearch(QueryBuilder $qb, string $entityFqcn, string $alias, string $q): void
    {
        try {
            $metadata     = $this->em->getClassMetadata($entityFqcn);
            $stringFields = array_filter(
                $metadata->getFieldNames(),
                static fn (string $name): bool => $metadata->getTypeOfField($name) === 'string'
            );
        } catch (\Exception) {
            return;
        }

        if (empty($stringFields)) {
            return;
        }

        $orX       = $qb->expr()->orX();
        $paramName = 'lexio_search_q';

        foreach ($stringFields as $field) {
            $orX->add($qb->expr()->like($alias . '.' . $field, ':' . $paramName));
        }

        $qb->andWhere($orX)->setParameter($paramName, '%' . $q . '%');
    }

    private function isSafeFieldName(string $field): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_.]*$/', $field);
    }
}

