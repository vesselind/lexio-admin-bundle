<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BulkContext
{
    /**
     * @var class-string<object>|null $entityFqcn
     */
    private ?string $entityFqcn = null;

    /** @var array<int> */
    private array  $ids        = [];

    public function __construct(
        private readonly RequestStack           $requestStack,
        private readonly EntityManagerInterface $manager,
    ) {
        $this->deserialize();
    }

    private function deserialize(): void
    {
        $query = $this->requestStack->getCurrentRequest()?->query->all();

        $this->entityFqcn = $query['entityFqcn'] ?? null;
        $this->ids        = $query['ids']        ?? null;
    }

    public function getEntityFqcn(): ?string
    {
        return $this->entityFqcn;
    }

    /**
     * @return array<int>|null
     */
    public function getIds(): ?array
    {
        return $this->ids;
    }

    /**
     * @return list<object>
     */
    public function getEntities(): array
    {
        if ($this->entityFqcn === null || empty($this->ids)) {
            return [];
        }

        return $this->manager->getRepository($this->entityFqcn)->findBy(['id' => $this->ids]);
    }
}

