<?php

namespace Lexio\AdminBundle\Contract\Filter;

use Lexio\AdminBundle\Form\Filter\BaseFilterType;
use Symfony\Component\PropertyAccess\PropertyAccess;

interface BaseFilterInterface
{
    public function getFormClass(): string;

    public function hasFilledProperty(): bool;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

}