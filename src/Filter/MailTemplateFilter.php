<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Filter;


final class MailTemplateFilter extends \Lexio\AdminBundle\Filter\BaseFilter
{
    public ?string $name = null;
    public ?string $content = null;
    public ?string $subject = null;
}
