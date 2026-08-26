<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Action;

final class UpdateAction
{
    public static function new(
        string $route,
        string $label = 'button.update',
        string $icon  = 'fa fa-edit',
    ): Action {
        return Action::new($label, $route, ['id' => 'id'], $icon);
    }
}

