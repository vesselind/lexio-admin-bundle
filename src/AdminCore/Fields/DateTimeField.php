<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

final class DateTimeField extends BaseField
{
    public function __construct(
        public readonly bool   $dateOnly   = false,
        public readonly string $badgeColor = 'blue',
        public readonly string $textColor  = 'white',
    ) {
    }
}

