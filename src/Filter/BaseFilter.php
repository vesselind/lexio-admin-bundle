<?php

namespace Lexio\AdminBundle\Filter;

use Lexio\AdminBundle\Contract\Filter\BaseFilterInterface;
use Lexio\AdminBundle\Form\Filter\BaseFilterType;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BaseFilter implements BaseFilterInterface
{
    public function getFormClass(): string
    {
        return BaseFilterType::class;
    }

    public function hasFilledProperty(): bool
    {
        $reflection = new \ReflectionClass($this);

        $fields = $reflection->getProperties();

        return array_any($fields, fn($field) => $field->getValue($this) !== null);

    }

    public function toArray(): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $reflection = new \ReflectionClass($this);
        $fields = $reflection->getProperties();

        $data = [];
        foreach ($fields as $field) {
            $fieldName = $field->getName();
            if ($accessor->isReadable($this, $fieldName)) {
                $data[$fieldName] = $accessor->getValue($this, $fieldName);

                if (is_object($data[$fieldName])) {
                    $data[$fieldName] = $data[$fieldName]->getId();
                }
            }
        }

        return $data;

    }
}