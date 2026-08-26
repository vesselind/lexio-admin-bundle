<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Listing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Fields\BaseField;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Form\Filter\BaseFilterType;
use Lexio\AdminBundle\Utils\AdminUtils;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

class ListingContext
{
    public const int DEFAULT_ITEMS_PER_PAGE = 20;

    /** @var array<string, Column> */
    private array           $columns             = [];
    private ?string         $entityFqcn          = null;
    private ?BaseFilter     $filter              = null;
    private ?string         $filterTypeClass     = null;
    private bool            $showCreateButton    = true;

    /** @var ArrayCollection<int, BulkAction> */
    private ArrayCollection $bulkActions;

    public function __construct(
        private readonly RequestStack           $requestStack,
        private readonly RouterInterface        $router,
        private readonly EntityManagerInterface $entityManager,
        public readonly PaginatorInterface      $paginator,
    ) {
        $this->bulkActions = new ArrayCollection();
    }

    public function addColumn(string $propertyName, BaseField $field): static
    {
        $this->columns[$propertyName] = new Column(
            $propertyName,
            AdminUtils::toSnakeCase($propertyName),
            $field,
            $this
        );

        return $this;
    }

    /**
     * @return array<string, Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addBulkAction(BulkAction $bulkAction): static
    {
        $this->bulkActions->add($bulkAction);

        return $this;
    }

    /**
     * @return ArrayCollection<int, BulkAction>
     */
    public function getBulkActions(): ArrayCollection
    {
        return $this->bulkActions;
    }

    public function setFilter(?BaseFilter $filter, ?string $filterTypeClass = BaseFilterType::class): static
    {
        $this->filter          = $filter;
        $this->filterTypeClass = $filterTypeClass;

        return $this;
    }

    public function getFilter(): ?BaseFilter
    {
        return $this->filter;
    }

    public function getFilterFormType(): string
    {
        Assert::notNull($this->filterTypeClass, 'Filter type class not set. Call setFilter() first.');

        return $this->filterTypeClass;
    }

    public function getCurrentRoute(): string
    {
        Assert::notNull($this->requestStack->getCurrentRequest(), 'Request not available. You should call this method only in HTTP context.');

        return $this->requestStack->getCurrentRequest()->attributes->get('_route');
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        Assert::notNull($this->requestStack->getCurrentRequest(), 'Request not available. You should call this method only in HTTP context.');

        return $this->requestStack->getCurrentRequest()->query->all();
    }

    public function setEntityFqcn(string $entityFqcn): static
    {
        $this->entityFqcn = $entityFqcn;

        $this->checkEntityFqcn();

        foreach ($this->getColumns() as $column) {
            $column->setEntityFqcn($entityFqcn);
        }

        return $this;
    }

    /**
     * Makes columns sortable when they map to a real database field on the entity.
     */
    public function refreshColumnsSortability(): static
    {
        $this->checkEntityFqcn();

        /** @phpstan-ignore-next-line */
        $entityFields = $this->entityManager->getClassMetadata($this->entityFqcn)->fieldNames;
        $properties   = array_values($entityFields);

        foreach ($this->getColumns() as $column) {
            $column->setSortable(in_array($column->propertyName, $properties, true));
        }

        return $this;
    }

    public function getRequest(): Request
    {
        Assert::notNull($this->requestStack->getCurrentRequest(), 'Request not available. You should call this method only in HTTP context.');

        return $this->requestStack->getCurrentRequest();
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function setItemsPerPage(int $itemsPerPage): static
    {
        $route = $this->getRequest()->attributes->get('_route');

        $this->requestStack->getSession()->set('itemsPerPage_' . $route, $itemsPerPage);

        return $this;
    }

    public function getItemsPerPage(): int
    {
        $route = $this->getRequest()->attributes->get('_route');

        return $this->requestStack->getSession()->get('itemsPerPage_' . $route, self::DEFAULT_ITEMS_PER_PAGE);
    }

    public function setShowCreateButton(bool $showCreateButton): static
    {
        $this->showCreateButton = $showCreateButton;

        return $this;
    }

    public function getShowCreateButton(): bool
    {
        return $this->showCreateButton;
    }

    private function checkEntityFqcn(): void
    {
        if (!$this->entityFqcn) {
            throw new RuntimeException('Entity FQCN not set. Call setEntityFqcn() first.');
        }

        if (!class_exists($this->entityFqcn)) {
            throw new RuntimeException(
                sprintf('Entity class "%s" does not exist. Check the FQCN passed to setEntityFqcn().', $this->entityFqcn)
            );
        }
    }
}

