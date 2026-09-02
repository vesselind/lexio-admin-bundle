<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

final class InlineEditField extends BaseField
{
    public function twigComponent(): string
    {
        return 'Admin:InlineEdit';
    }
}

