<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuBuilder
{
    /** @var ArrayCollection<int, MenuItemInterface> */
    private ArrayCollection $menuItems;

    public function __construct(private readonly RequestStack $requestStack)
    {
        $this->menuItems = new ArrayCollection();
    }

    public function addLink(string $title, string $url, ?string $icon = null, ?MenuBadge $badge = null): static
    {
        $this->menuItems->add(new Link($title, $url, $this->requestStack, $icon, $badge));

        return $this;
    }

    public function addSubMenu(string $title, ?string $icon = null, ?MenuBadge $badge = null): SubMenu
    {
        $subMenu = new SubMenu($this, $this->requestStack)
            ->setTitle($title)
            ->setIcon($icon)
            ->setBadge($badge);

        $this->menuItems->add($subMenu);

        return $subMenu;
    }

    public function addSeparator(Separator $separator): static
    {
        $this->menuItems->add($separator);

        return $this;
    }

    /**
     * @return ArrayCollection<int, MenuItemInterface>
     */
    public function getMenuItems(): ArrayCollection
    {
        return $this->menuItems;
    }
}

