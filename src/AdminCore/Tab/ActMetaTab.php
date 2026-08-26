<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Tab;

use Lexio\AdminBundle\Utils\AdminUtils;

/**
 * A tab that links to the 'meta' admin page of an entity.
 * Route convention: admin.{entity_snake}.meta
 */
class ActMetaTab extends Tab
{
    public function __construct(
        private ?string $title      = 'tab.act_meta',
        private ?string $pageRoute  = '',
        private array   $routeParams = [],
    ) {
        parent::__construct($title ?? 'tab.act_meta', $pageRoute ?? '', $routeParams);
    }

    public function getTitle(): ?string
    {
        return $this->title ?? 'tab.act_meta';
    }

    public function getRoute(): string
    {
        if ($this->getEntityInstance() === null) {
            throw new \InvalidArgumentException(
                '[ActMetaTab] Entity instance is not set. Either set the entity instance or provide explicit routeParams.'
            );
        }

        if (!empty($this->pageRoute)) {
            return $this->pageRoute;
        }

        return sprintf('admin.%s.meta', AdminUtils::classNameToSnake(get_class($this->getEntityInstance())));
    }

    public function getRouteParams(): array
    {
        if (empty($this->routeParams) && $this->getEntityInstance() === null) {
            throw new \InvalidArgumentException(
                '[ActMetaTab] Entity instance is not set. Either set the entity instance or provide explicit routeParams.'
            );
        }

        if (!empty($this->routeParams)) {
            return $this->routeParams;
        }

        return ['id' => $this->getEntityInstance()->getId()];
    }
}

