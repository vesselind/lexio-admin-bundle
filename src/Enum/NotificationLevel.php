<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

use Lexio\AdminBundle\Enum\TranslatableEnum;

enum NotificationLevel: string
{
    use TranslatableEnum;

    case IMPORTANT = 'important';
    case INFO = 'info';

    public function isSendingMailRequired(): bool
    {
        return match ($this) {
            self::IMPORTANT => true,
            default => false,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IMPORTANT => 'danger',
            self::INFO => 'primary',
        };
    }
}
