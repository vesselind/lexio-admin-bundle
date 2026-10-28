<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Api;

use Lexio\AdminBundle\Contract\Filter\BaseFilterInterface;

interface ApiQueryInterface extends BaseFilterInterface
{
    public const int DEFAULT_PAGE_ITEMS = 20;

    public function getPage(): int;

    public function setPage(?int $page): static;

    public function getLimit(): int;

    public function setLimit(?int $limit): static;

    public function getSearchPhrase(): string;

    public function setSearchPhrase(?string $searchPhrase): static;

    public function getSort(): ?string;

    /**
     * Optionally override the sortable fields; ApiQueryFields returns null by default.
     * Null uses the bundle's EntitySortabilityPropertiesResolver for the controller's entity.
     * An empty array disables explicit sorting. A non-empty list takes priority over the resolver.
     *
     * @return list<string>|null
     */
    public static function sortableFields(): ?array;

    public function getOrder(): ?string;

    public function getLocale(): ?string;
}
