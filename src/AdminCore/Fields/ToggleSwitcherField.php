<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

final class ToggleSwitcherField extends BaseField
{
    public function twigComponent(): string
    {
        return 'Admin:ToggleSwitcher';
    }
}

