<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

enum FileTypes: string
{
    case IMAGE    = 'image';
    case DOCUMENT = 'document';
}

