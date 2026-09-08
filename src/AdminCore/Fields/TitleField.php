<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
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
    /**
     * @param array<string, string> $routeParams
     */
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

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(object $entityInstance): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        return array_map(
            static fn (string $propertyPath): mixed => $accessor->getValue($entityInstance, $propertyPath),
            $this->routeParams
        );
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getImage(): ?ImageEntityInterface
    {
        if ($this->imageProperty === null || $this->getEntityInstance() === null) {
            return null;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $image = $accessor->getValue($this->getEntityInstance(), $this->imageProperty);

        if ($image === null || $image instanceof ImageEntityInterface) {
            return $image;
        }

        throw new \UnexpectedValueException(sprintf(
            'The image property "%s" must contain an %s or null.',
            $this->imageProperty,
            ImageEntityInterface::class,
        ));
    }
}

