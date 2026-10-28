<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Api\Output\TranslationEntryOutput;

use Lexio\AdminBundle\Api\Payload\TranslationEntryPayload;
use Lexio\AdminBundle\Api\Query\TranslationEntryListQuery;
use Lexio\AdminBundle\Api\Response\PaginatedResponse;
use Lexio\AdminBundle\Api\Service\TranslationEntryManager;
use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\AdminCore\Resolver\MapQueryStringValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

abstract class TranslationEntryController extends AbstractController
{
    public function __construct(
        private readonly TranslationEntryManager $entries,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route('/list', name: 'api.v1.translation_entry.list', methods: ['GET'])]
    public function list(
        #[MapQueryString(resolver: MapQueryStringValueResolver::class, validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        TranslationEntryListQuery $query,
        Request $request,
    ): JsonResponse {
        $query->setLocale($request->getLocale());
        $pagination = $this->paginator->paginate($this->entries->list($query), $query->getPage(), $query->getLimit());
        $items = [];
        foreach ($pagination->getItems() as $item) {
            if (!$item instanceof TranslationEntryOutput) {
                throw new \LogicException('Translation entry pagination must contain output DTOs.');
            }
            $items[] = $item;
        }

        return $this->json(
            new PaginatedResponse($items, $query->getPage(), $query->getLimit(), $pagination->getTotalItemCount()),
            Response::HTTP_OK,
            [],
            ['groups' => ['api:list']],
        );
    }

    #[Route('/{domain}/{key}/show', name: 'api.v1.translation_entry.show', methods: ['GET'])]
    public function show(string $domain, string $key, Request $request): JsonResponse
    {
        return $this->json($this->entries->show($domain, $key, $request->getLocale()), Response::HTTP_OK, [], ['groups' => ['api:view']]);
    }

    #[Route('/{domain}/{key}/update', name: 'api.v1.translation_entry.update', methods: ['PATCH'])]
    public function update(string $domain, string $key, #[MapRequestPayload] TranslationEntryPayload $payload, Request $request): JsonResponse
    {
        return $this->json(
            $this->entries->update($domain, $key, $payload->value, $request->getLocale()),
            Response::HTTP_OK,
            [],
            ['groups' => ['api:view']],
        );
    }
}
