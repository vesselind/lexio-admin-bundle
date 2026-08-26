<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Model;

/**
 * Parameters returned by SearchableEntityInterface::getSearchableParameters().
 */
final class SearchableParam
{
    public function __construct(
        public ?string $title           = null,
        public array   $searchableFields = [],
        public ?string $routeName       = null,
        public array   $routeParams     = [],
        public ?string $icon            = null,
        public ?string $subTitle        = null,
    ) {
    }
}

