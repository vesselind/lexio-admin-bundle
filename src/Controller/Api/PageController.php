<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Api\Output\ContentItemListOutput;
use Lexio\AdminBundle\Api\Output\PageDetailOutput;
use Lexio\AdminBundle\Api\Output\PageOutput;
use Lexio\AdminBundle\Api\Payload\PagePayload;
use Lexio\AdminBundle\Api\Query\ContentItemListQuery;
use Lexio\AdminBundle\Api\Query\PageListQuery;
use Lexio\AdminBundle\Api\Service\PageContentItemUpdater;
use Lexio\AdminBundle\AdminCore\Resolver\MapQueryStringValueResolver;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Page\BasePage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

abstract class PageController extends BaseApiController
{
    public function getEntityFqcn(): string
    {
        return Page::class;
    }

    #[Route('/list', name: 'api.v1.page.list', methods: ['GET'])]
    public function list(
        #[MapQueryString(resolver: MapQueryStringValueResolver::class, validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        PageListQuery $query,
    ): JsonResponse {
        return $this->paginatedResponse($query, new PageOutput());
    }

    #[Route('/content-items/list', name: 'api.v1.page.content_item.list', methods: ['GET'])]
    public function listContentItems(
        #[MapQueryString(resolver: MapQueryStringValueResolver::class, validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ContentItemListQuery $query,
    ): JsonResponse {
        return $this->paginatedResponse($query, new ContentItemListOutput(), entityFqcn: ContentItem::class);
    }

    #[Route('/{id}/show', name: 'api.v1.page.show', methods: ['GET'])]
    public function show(Page $page, #[MapQueryParameter] ?string $locale = null): JsonResponse
    {
        return $this->viewResponse($page, new PageDetailOutput(), locale: $locale);
    }

    #[Route('/{id}/update', name: 'api.v1.page.update', methods: ['PATCH'])]
    #[IsGranted('ROLE_EDITOR')]
    public function update(
        Page $page,
        #[MapRequestPayload] PagePayload $payload,
        PageAdministrationInterface $pageManager,
        PageContentItemUpdater $contentItemUpdater,
    ): JsonResponse {
        $locale = $this->switchLocale($payload->getLocale());

        $pageClass = $page->getName();

        if ($pageClass === null || !class_exists($pageClass) || !is_subclass_of($pageClass, BasePage::class)) {
            throw $this->createNotFoundException();
        }

        $pageObject = $pageManager->getPageObject($pageClass, $locale);

        if (!$pageObject instanceof BasePage) {
            throw $this->createNotFoundException();
        }

        $contentItemUpdater->apply($pageObject, $payload->contentItems);

        $pageManager->createOrUpdatePage($pageObject, $locale);

        return $this->viewResponse($page, new PageDetailOutput(), locale: $locale);
    }
}
