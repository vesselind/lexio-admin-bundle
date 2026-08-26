<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Menu;

use Closure;

/**
 * Optional metadata rendered alongside a menu item's title.
 *
 * The callback is evaluated when the value is read by Twig, which keeps
 * counters and other state-dependent values current while rendering.
 */
final readonly class MenuBadge
{
    private ?Closure $callback;

    /**
     * @param callable(): (string|int|null)|null $callback
     */
    public function __construct(
        private string|int|null $caption = null,
        private ?string $icon = null,
        private ?string $color = null,
        ?callable $callback = null,
    ) {
        $this->callback = $callback !== null ? Closure::fromCallable($callback) : null;
    }

    public function getCaption(): string|int|null
    {
        $caption = $this->callback !== null ? ($this->callback)() : $this->caption;

        return $caption === '' ? null : $caption;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}
