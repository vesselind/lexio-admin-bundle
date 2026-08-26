<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Items-per-page switcher live component.
 *
 * Updates the ?limit= URL parameter and redirects so the listing controller
 * picks up the new value.
 *
 * Usage:
 *   <twig:Admin:ItemsPerPage itemsPerPage="{{ currentLimit }}" />
 */
#[AsLiveComponent(name: 'Admin:ItemsPerPage', template: '@LexioAdmin/components/Admin/ItemsPerPage.html.twig')]
final class ItemsPerPage
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $itemsPerPage = 25;

    /** @var int[] */
    public array $options = [10, 20, 50, 100, 300];

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function mount(int $itemsPerPage = 25, array $options = []): void
    {
        $this->itemsPerPage = $itemsPerPage;

        if (!empty($options)) {
            $this->options = $options;
        }
    }

    #[LiveAction]
    public function reloadPage(): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $params = $request->query->all();
        $params['limit'] = $this->itemsPerPage;

        return new RedirectResponse($request->getPathInfo() . '?' . http_build_query($params));
    }
}

