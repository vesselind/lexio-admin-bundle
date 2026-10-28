<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Query;

use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @phpstan-require-implements ApiQueryInterface
 */
trait ApiQueryFields
{
    #[SerializedName('page')]
    #[Assert\Positive]
    private ?int $page = null;

    #[SerializedName('limit')]
    #[Assert\Range(min: 1, max: 100)]
    private ?int $limit = null;

    #[SerializedName('q')]
    #[Assert\Length(max: 255)]
    private ?string $searchPhrase = null;


    private ?string $locale = null;

    private ?string $sort = null;

    #[Assert\Regex(pattern: '/^(asc|desc)$/i')]
    private ?string $order = null;

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page ?? 1;
    }

    public function setPage(?int $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit ?? self::DEFAULT_PAGE_ITEMS;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }


    public function getSearchPhrase(): string
    {
        return $this->searchPhrase ?? '';
    }

    public function setSearchPhrase(?string $searchPhrase): static
    {
        $this->searchPhrase = $searchPhrase;

        return $this;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    /** @return list<string>|null */
    public static function sortableFields(): ?array
    {
        return null;
    }

    public function setSort(?string $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function setOrder(?string $order): static
    {
        $this->order = $order;

        return $this;
    }
}
