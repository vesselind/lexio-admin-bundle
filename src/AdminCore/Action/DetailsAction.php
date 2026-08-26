<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Action;

final class DetailsAction
{
    public static function new(
        string $route,
        string $label = 'button.details',
        string $icon  = 'fa fa-eye',
    ): Action {
        return Action::new($label, $route, ['id' => 'id'], $icon);
    }
}

