<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Filter;

final class SecurityLogFilter extends BaseFilter
{
    public ?string $ipAddress = null;
    public ?string $userAgent = null;
    public ?string $actingUser = null;
    public ?string $affectedUser = null;
}
