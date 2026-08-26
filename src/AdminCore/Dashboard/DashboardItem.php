<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Dashboard;

final class DashboardItem
{
    public function __construct(
        public readonly string  $title,
        public readonly string  $count,
        public readonly ?string $icon       = null,
        public readonly ?string $listUrl    = null,
        public readonly ?string $addUrl     = null,
        public readonly ?string $iconColor  = null,
        public readonly ?string $subCount   = null,
    ) {
    }
}

