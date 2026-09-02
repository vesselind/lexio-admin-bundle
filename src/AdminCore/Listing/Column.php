<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Listing;

use Lexio\AdminBundle\AdminCore\Fields\BaseField;

class Column
{
    private bool    $isSortable = false;

    /** @var class-string|null */
    private ?string $entityFqcn = null;

    public function __construct(
        public readonly string         $propertyName,
        public readonly string         $fieldName,
        public readonly BaseField      $field,
        public readonly ListingContext $listingContext,
    ) {
        $this->field->setColumn($this);
    }

    public function getSortLink(): string
    {
        $newOrder = ($this->isSorted() === false || $this->isSortedDesc() === true) ? 'asc' : 'desc';

        $routeParams = array_merge(
            $this->listingContext->getQueryParams(),
            ['sort' => $this->propertyName, 'order' => $newOrder]
        );

        return $this->listingContext->getRouter()->generate(
            $this->listingContext->getCurrentRoute(),
            $routeParams
        );
    }

    public function setSortable(bool $sortable): void
    {
        $this->isSortable = $sortable;
    }

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    public function isSorted(): bool
    {
        return $this->listingContext->getRequest()->query->get('sort') === $this->propertyName;
    }

    public function isSortedAsc(): bool
    {
        return $this->isSorted() && $this->listingContext->getRequest()->query->get('order') === 'asc';
    }

    public function isSortedDesc(): bool
    {
        return $this->isSorted() && $this->listingContext->getRequest()->query->get('order') === 'desc';
    }

    /**
     * @param class-string $entityFqcn
     */
    public function setEntityFqcn(string $entityFqcn): void
    {
        $this->entityFqcn = $entityFqcn;
    }

    /**
     * @return class-string
     */
    public function getEntityFqcn(): string
    {
        if ($this->entityFqcn === null) {
            throw new \LogicException('Entity FQCN must be set before reading the column entity.');
        }

        return $this->entityFqcn;
    }

    public function getField(): BaseField
    {
        return $this->field;
    }
}

