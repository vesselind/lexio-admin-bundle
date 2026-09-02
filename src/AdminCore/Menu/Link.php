<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Component\HttpFoundation\RequestStack;

class Link implements MenuItemInterface
{
    public function __construct(
        public readonly string  $title,
        public readonly string  $url,
        private readonly RequestStack $requestStack,
        public readonly ?string $icon = null,
        private readonly ?MenuBadge $badge = null,
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
        return $this->badge;
    }

    public function isActive(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return false;
        }

        return $this->url === $request->getPathInfo();
    }
}

