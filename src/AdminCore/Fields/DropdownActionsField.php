<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Lexio\AdminBundle\AdminCore\Action\Action;
use Doctrine\Common\Collections\ArrayCollection;

class DropdownActionsField extends BaseField
{
    private ArrayCollection $actions;
    private ArrayCollection $actionsWithConfirmationModal;

    public function __construct()
    {
        $this->actions                      = new ArrayCollection();
        $this->actionsWithConfirmationModal  = new ArrayCollection();
    }

    public function mapped(): bool
    {
        return false;
    }

    public function addAction(Action $action): static
    {
        $action->setParentField($this);

        $this->actions->add($action);

        return $this;
    }

    /**
     * @return ArrayCollection<int, Action>
     */
    public function getActions(): ArrayCollection
    {
        return $this->actions;
    }

    /**
     * @return ArrayCollection<int, Action>
     */
    public function actionsWithConfirmationModal(): ArrayCollection
    {
        if ($this->actionsWithConfirmationModal->isEmpty()) {
            $this->actionsWithConfirmationModal = $this->actions->filter(
                static fn (Action $action): bool => $action->hasConfirmationModal()
            );
        }

        return $this->actionsWithConfirmationModal;
    }
}

