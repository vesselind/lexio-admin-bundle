<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Filter;

use Lexio\AdminBundle\Filter\BaseFilter;

final class SystemNotificationFilter extends BaseFilter
{
    public ?string $title = null;
    public ?string $content = null;
    public ?string $userEmail = null;
}
