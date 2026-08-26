<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Renders a clickable title cell that links to a route.
 *
 * Route format example:
 *   linkToRoute: 'admin.blog.update'
 *   routeParams: ['id' => 'id']   // second 'id' is a property path on the entity
 */
class TitleField extends BaseField
{
    public function __construct(
        public readonly string  $linkToUrl     = '',
        public readonly string  $linkToRoute   = '',
        public readonly ?string $target        = '_self',
        private readonly array  $routeParams   = [],
        private readonly string $class         = '',
        public readonly ?string $imageProperty = null,
    ) {
    }

    public function hasRouteParams(): bool
    {
        return !empty($this->routeParams);
    }

    public function getRouteParams(object $entityInstance): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return array_map(
            static fn (mixed $propertyPath) => $accessor->getValue($entityInstance, $propertyPath),
            $this->routeParams
        );
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getImage(): ?string
    {
        if ($this->imageProperty === null || $this->getEntityInstance() === null) {
            return null;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        return $accessor->getValue($this->getEntityInstance(), $this->imageProperty);
    }
}

