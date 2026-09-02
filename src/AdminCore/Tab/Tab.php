<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Tab;

class Tab implements TabInterface
{
    private ?object $entityInstance = null;

    /**
     * @param array<string, mixed> $routeParams
     */
    public function __construct(
        private readonly string $title,
        private readonly string $pageRoute,
        private readonly array  $routeParams = [],
    ) {
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getRoute(): ?string
    {
        return $this->pageRoute;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function setEntityInstance(object $entity): static
    {
        $this->entityInstance = $entity;

        return $this;
    }

    public function getEntityInstance(): ?object
    {
        return $this->entityInstance;
    }
}

