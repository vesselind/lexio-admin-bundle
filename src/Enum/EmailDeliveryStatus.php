<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

enum EmailDeliveryStatus: string
{
    use TranslatableEnum;

    case PENDING = 'pending';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SUCCESSFUL => 'success',
            self::FAILED => 'danger',
        };
    }
}
