<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Listing;

use Doctrine\ORM\EntityManagerInterface;

final readonly class EntitySortabilityPropertiesResolver
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param class-string $entityFqcn
     * @return list<string>
     */
    public function resolve(string $entityFqcn): array
    {
        return array_values($this->entityManager->getClassMetadata($entityFqcn)->fieldNames);
    }
}
