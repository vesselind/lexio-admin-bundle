<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component;

use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Abstract image selector live component.
 *
 * Extend this for each entity that can have an image attached/detached:
 *
 *   #[AsLiveComponent('Admin:PostImageSelector')]
 *   class PostImageSelector extends AbstractImageSelector
 *   {
 *       protected function handleDetach(): void
 *       {
 *           $post = $this->postRepository->find($this->entityId);
 *           $post->setCoverImage(null);
 *           $this->entityManager->flush();
 *       }
 *   }
 */
abstract class AbstractImageSelector
{
    use DefaultActionTrait;

    /** Derived public URL for the current image; never persisted. */
    #[LiveProp]
    public ?string $imageUrl = null;

    /** Current managed image ID, when one is attached. */
    #[LiveProp]
    public int|string|null $imageId = null;

    /** Entity primary key — used in the confirmation modal target ID. */
    #[LiveProp]
    public int|string|null $entityId = null;

    public function mount(
        int|string|null $entityId = null,
        ?string $imageUrl = null,
        int|string|null $imageId = null,
    ): void
    {
        $this->entityId = $entityId;
        $this->imageUrl = $imageUrl;
        $this->imageId = $imageId;
    }

    /**
     * Implement this to detach the image from the entity (clear the field + flush).
     */
    abstract protected function handleDetach(): void;

    #[LiveAction]
    public function confirm(): void
    {
        $this->handleDetach();
        $this->imageUrl = null;
        $this->imageId = null;
    }
}

