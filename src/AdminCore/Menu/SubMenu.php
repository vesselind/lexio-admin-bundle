<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Component\HttpFoundation\RequestStack;

class SubMenu implements MenuItemInterface
{
    private ?string $title    = null;
    private ?string $icon     = null;
    private ?MenuBadge $badge = null;
    private string  $uniqueId;

    /** @var ArrayCollection<int, Link> */
    private ArrayCollection $links;

    public function __construct(
        private readonly MenuBuilder  $menuBuilder,
        private readonly RequestStack $requestStack,
    ) {
        $this->uniqueId = uniqid('sub_menu_', false);
        $this->links    = new ArrayCollection();
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function addLink(string $title, string $url, ?string $icon = null, ?MenuBadge $badge = null): static
    {
        $link = new Link($title, $url, $this->requestStack, $icon, $badge);

        if (!$this->links->contains($link)) {
            $this->links->add($link);
        }

        return $this;
    }

    /**
     * @return ArrayCollection<int, Link>
     */
    public function getLinks(): ArrayCollection
    {
        return $this->links;
    }

    public function end(): MenuBuilder
    {
        return $this->menuBuilder;
    }

    public function getType(): string
    {
        return AdminUtils::getClassName(self::class);
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getBadge(): ?MenuBadge
    {
        return $this->badge;
    }

    public function setBadge(?MenuBadge $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    public function getId(): string
    {
        return $this->uniqueId;
    }

    public function isActive(): bool
    {
        foreach ($this->links as $link) {
            if ($link->isActive()) {
                return true;
            }
        }

        return false;
    }
}

