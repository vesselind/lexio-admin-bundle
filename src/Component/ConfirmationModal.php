<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Generic confirmation modal live component.
 *
 * Usage:
 *   <button data-bs-toggle="modal" data-bs-target="#confirmationModal_{{ entity.id }}">Delete</button>
 *
 *   <twig:ConfirmationModal
 *       subjectId="{{ entity.id }}"
 *       confirmUrl="{{ path('admin.post.delete', {id: entity.id}) }}"
 *   >
 *       Are you sure you want to delete this item?
 *   </twig:ConfirmationModal>
 *
 * A host live component can instead pass `dispatchEventName` to handle the
 * confirmation locally without a redirect.
 *
 * The confirm() LiveAction stores the subjectId in the session under CONFIRMED_SESSION_KEY
 * and redirects to confirmUrl. The delete controller must check this key before proceeding.
 */
#[AsLiveComponent(name: 'ConfirmationModal', template: '@LexioAdmin/components/ConfirmationModal.html.twig')]
final class ConfirmationModal
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    /**
     * Session key under which the confirmed entity ID is stored.
     * Check this in your delete controller before executing the operation.
     */
    public const string CONFIRMED_SESSION_KEY = '_confirmation_modal_id';

    /** Entity identifier stored in the session and emitted with confirmation events. */
    #[LiveProp]
    public string $subjectId = '';

    /** Unique DOM identifier suffix. Defaults to the subject ID for single-action modals. */
    #[LiveProp]
    public string $modalId = '';

    /** URL to redirect to after the user confirms. */
    #[LiveProp]
    public string $confirmUrl = '';

    /** Optional live event emitted to the parent component after confirmation. */
    #[LiveProp]
    public ?string $dispatchEventName = null;

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function mount(
        string $subjectId,
        string $confirmUrl = '',
        ?string $dispatchEventName = null,
        string $modalId = '',
    ): void
    {
        $this->subjectId = $subjectId;
        $this->modalId = '' === $modalId ? $subjectId : $modalId;
        $this->confirmUrl = $confirmUrl;
        $this->dispatchEventName = $dispatchEventName;
    }

    #[LiveAction]
    public function confirm(): ?RedirectResponse
    {
        $this->requestStack->getSession()->set(self::CONFIRMED_SESSION_KEY, $this->subjectId);

        $dispatchEventName = $this->dispatchEventName;
        if (null !== $dispatchEventName && '' !== $dispatchEventName) {
            $this->dispatchBrowserEvent('modal:close');
            $this->emitUp($dispatchEventName, ['subjectId' => $this->subjectId]);

            if ('' === $this->confirmUrl) {
                return null;
            }
        }

        if ($this->confirmUrl !== '') {
            return new RedirectResponse($this->confirmUrl);
        }

        // Fallback: reload current page so the caller can react.
        return new RedirectResponse(
            $this->requestStack->getCurrentRequest()?->getUri() ?? '/'
        );
    }
}
