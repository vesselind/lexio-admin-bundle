<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

interface MenuItemInterface
{
    public function getType(): string;

    public function getTitle(): string;

    public function getBadge(): ?MenuBadge;
}

