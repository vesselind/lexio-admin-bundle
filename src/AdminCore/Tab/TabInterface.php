<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Tab;

interface TabInterface
{
    public function getTitle(): ?string;

    public function getRoute(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array;

    public function setEntityInstance(object $entity): static;

    public function getEntityInstance(): ?object;
}

