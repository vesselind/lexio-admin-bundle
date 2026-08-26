<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

enum FileAccessType: string
{
    case PUBLIC  = 'public';
    case PRIVATE = 'private';

    /** Returns the filesystem directory prefix for this access type (no trailing slash). */
    public function directory(): string
    {
        return match ($this) {
            self::PUBLIC  => 'public',
            self::PRIVATE => 'private',
        };
    }
}

