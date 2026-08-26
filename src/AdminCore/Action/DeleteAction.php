<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Action;

final class DeleteAction
{
    public static function new(
        string $route,
        string $label = 'button.delete',
        string $icon  = 'fa fa-trash',
    ): Action {
        return Action::new($label, $route, ['id' => 'id'], $icon)
            ->confirmationModal('delete_record.confirmation_text', $route, ['id' => 'id'])
            ->setTextClass('text-danger');
    }
}

