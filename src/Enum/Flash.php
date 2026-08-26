<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

enum Flash: string
{
    case SUCCESS = 'success';
    case ERROR   = 'error';
    case WARNING = 'warning';
    case INFO    = 'info';
}

