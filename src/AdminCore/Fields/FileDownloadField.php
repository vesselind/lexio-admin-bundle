<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Field that renders a downloadable file link.
 *
 * Construct the route in the following format:
 *   admin.blog.download, routeParams: ['id' => 'id']
 * where the second 'id' is a property path on the entity.
 */
class FileDownloadField extends BaseField
{
    public function __construct(
        public readonly string $label,
        public readonly string $linkToUrl   = '',
        public readonly string $linkToRoute = '',
        private readonly array $routeParams = [],
    ) {
    }

    public function hasRouteParams(): bool
    {
        return !empty($this->routeParams);
    }

    public function getRouteParams(): array
    {
        $accessor       = PropertyAccess::createPropertyAccessor();
        $entityInstance = $this->getEntityInstance();

        return array_map(
            static fn (mixed $propertyPath) => $accessor->getValue($entityInstance, $propertyPath),
            $this->routeParams
        );
    }
}

