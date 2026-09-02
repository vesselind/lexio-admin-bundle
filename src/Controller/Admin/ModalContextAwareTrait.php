<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Trait for controllers that support modal context operations.
 * Sets created/updated entities in request attributes for the ModalContextSubscriber.
 */
trait ModalContextAwareTrait
{
    /**
     * Mark an entity as created in the current request for modal context handling.
     * This allows the ModalContextSubscriber to return the entity data to the parent form.
     */
    protected function setCreatedEntityForModal(Request $request, object $entity): void
    {
        $request->attributes->set('_created_entity', $entity);
    }
}
