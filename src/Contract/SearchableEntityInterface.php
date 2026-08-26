<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract;

use Lexio\AdminBundle\Model\SearchableParam;

/**
 * Implement this on any entity that should be searchable via EntitySearcher.
 *
 * Example:
 *
 *   class Post implements SearchableEntityInterface
 *   {
 *       public function __toString(): string { return $this->title; }
 *
 *       public function getSearchableParameters(): SearchableParam
 *       {
 *           return new SearchableParam(
 *               title: 'Posts',
 *               searchableFields: ['title', 'content'],
 *               routeName: 'admin.post.update',
 *               routeParams: ['id' => 'id'],
 *               icon: 'fa-solid:pen',
 *           );
 *       }
 *   }
 */
interface SearchableEntityInterface
{
    public function __toString(): string;

    public function getSearchableParameters(): SearchableParam;
}

