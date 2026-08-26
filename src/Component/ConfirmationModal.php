<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
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
 * The confirm() LiveAction stores the subjectId in the session under CONFIRMED_SESSION_KEY
 * and redirects to confirmUrl. The delete controller must check this key before proceeding.
 */
#[AsLiveComponent(name: 'ConfirmationModal', template: '@LexioAdmin/components/ConfirmationModal.html.twig')]
final class ConfirmationModal
{
    use DefaultActionTrait;

    /**
     * Session key under which the confirmed entity ID is stored.
     * Check this in your delete controller before executing the operation.
     */
    public const string CONFIRMED_SESSION_KEY = '_confirmation_modal_id';

    /** Must match the `data-bs-target="#confirmationModal_{{ subjectId }}"` button. */
    public string $subjectId = '';

    /** URL to redirect to after the user confirms. */
    public string $confirmUrl = '';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function mount(string $subjectId, string $confirmUrl = ''): void
    {
        $this->subjectId = $subjectId;
        $this->confirmUrl = $confirmUrl;
    }

    #[LiveAction]
    public function confirm(): RedirectResponse
    {
        $this->requestStack->getSession()->set(self::CONFIRMED_SESSION_KEY, $this->subjectId);

        if ($this->confirmUrl !== '') {
            return new RedirectResponse($this->confirmUrl);
        }

        // Fallback: reload current page so the caller can react
        return new RedirectResponse(
            $this->requestStack->getCurrentRequest()?->getUri() ?? '/'
        );
    }
}
