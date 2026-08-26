<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Bulk;

final class BulkAction
{
    public function __construct(
        public readonly string  $route,
        public readonly string  $label,
        public readonly ?string $icon        = null,
        public readonly ?string $buttonColor = null,
        public readonly ?string $textColor   = null,
    ) {
    }
}

