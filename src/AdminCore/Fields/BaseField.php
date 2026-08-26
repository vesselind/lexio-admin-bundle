<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Lexio\AdminBundle\AdminCore\Listing\Column;
use Lexio\AdminBundle\Utils\AdminUtils;

abstract class BaseField
{
    private object|null $entityInstance = null;
    private ?Column     $column         = null;

    /**
     * The value of the field.
     * Only mapped fields can have a value; all fields can have an entity instance.
     */
    private mixed $value = null;

    public function setEntityInstance(object $entityInstance): static
    {
        $this->entityInstance = $entityInstance;

        return $this;
    }

    public function getEntityInstance(): ?object
    {
        return $this->entityInstance;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function templatePath(): string
    {
        $classSnakeName = AdminUtils::classNameToSnake(static::class);

        return '@LexioAdmin/admin/fields/' . $classSnakeName . '.html.twig';
    }

    public function setColumn(Column $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): ?Column
    {
        return $this->column;
    }

    public function mapped(): bool
    {
        return true;
    }

    public function twigComponent(): ?string
    {
        return null;
    }
}

