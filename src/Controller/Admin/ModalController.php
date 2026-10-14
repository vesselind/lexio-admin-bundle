<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\Contract\SearchableEntityInterface;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Service\EntitySearcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides the reusable admin modal endpoints (empty success frame and link search).
 *
 * Host applications extend this controller to add the route prefix and access
 * rule used by their own application route loader.
 */
abstract class ModalController extends BaseController
{
    /**
     * The entity class searched by the link-search modal.
     *
     * @return class-string<SearchableEntityInterface>
     */
    abstract public function getSearchableEntityFqcn(): string;

    #[Route('/modal-success', name: 'admin._modals.success')]
    public function modalSuccess(): Response
    {
        return $this->render('@LexioAdmin/admin/_modals/modal_success.html.twig');
    }

    #[Route('/links-search', name: 'admin._modals.links_search')]
    public function linksSearch(
        EntitySearcher $searchableEntityService,
        PaginatorInterface $paginator,
        Request $request,
        #[MapQueryParameter] ?string $query = null,
    ): Response {
        $searchResults = $searchableEntityService->search($this->getSearchableEntityFqcn(), $query);

        $items = $paginator->paginate($searchResults, $request->query->getInt('page', 1), 10);

        return $this->render('@LexioAdmin/admin/_modals/links_search.html.twig', [
            'items' => $items,
            'query' => $query,
        ]);
    }
}
