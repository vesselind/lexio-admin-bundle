<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Query;

use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;

use Lexio\AdminBundle\Filter\PageFilter;

final class PageListQuery extends PageFilter implements ApiQueryInterface
{
    use ApiQueryFields;

    public function getSort(): string
    {
        return $this->sort ?? 'id';
    }

    public function getOrder(): string
    {
        return $this->order ?? 'desc';
    }
}
