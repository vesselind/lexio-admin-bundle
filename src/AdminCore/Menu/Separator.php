<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

use Lexio\AdminBundle\Utils\AdminUtils;

class Separator implements MenuItemInterface
{
    public function __construct(
        public readonly string $title,
    ) {
    }

    public function getType(): string
    {
        return AdminUtils::getClassName(self::class);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBadge(): ?MenuBadge
    {
        return null;
    }
}

