<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Attributes;

#[\Attribute]
final readonly class Page
{
    public function __construct(public string $pageClass)
    {
    }
}
