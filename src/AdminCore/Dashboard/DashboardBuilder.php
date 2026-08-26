<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Dashboard;

use Doctrine\Common\Collections\ArrayCollection;

class DashboardBuilder
{
    private ArrayCollection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(
        string  $title,
        string  $count,
        ?string $icon       = null,
        ?string $listUrl    = null,
        ?string $addUrl     = null,
        ?string $iconColor  = 'primary',
        ?string $subCount   = null,
    ): static {
        $item = new DashboardItem($title, $count, $icon, $listUrl, $addUrl, $iconColor, $subCount);

        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }

        return $this;
    }

    /**
     * @return ArrayCollection<int, DashboardItem>
     */
    public function getItems(): ArrayCollection
    {
        return $this->items;
    }
}

