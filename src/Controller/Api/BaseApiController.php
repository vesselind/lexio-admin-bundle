<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Contract\Api\ApiPayloadInterface;
use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;
use Lexio\AdminBundle\Api\Response\FullListResponse;
use Lexio\AdminBundle\Api\Response\PaginatedResponse;
use Lexio\AdminBundle\Exception\ApiValidationException;
use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\AdminCore\Listing\EntitySortabilityPropertiesResolver;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Enum\FileTypes;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\File\FileValidator;
use Lexio\AdminBundle\File\ImageCacheResolver;
use Lexio\AdminBundle\Service\EntityFilterer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

abstract class BaseApiController extends BaseController
{
    /** @param list<string> $locales */
    public function __construct(
        private readonly EntitySortabilityPropertiesResolver $entitySortabilityPropertiesResolver,
        #[Autowire(param: 'lexio_admin.default_locale')]
        private readonly string $defaultLocale,
        #[Autowire(param: 'lexio_admin.locales')]
        private readonly array $locales,
    )
    {
    }

    /**
     * Returns the fully qualified class name of the entity that this controller manages.
     * @return class-string
     * @phpstan-return class-string
     */
    abstract public function getEntityFqcn(): string;

    public function filterService(): EntityFilterer
    {
        return $this->container->get(EntityFilterer::class);
    }

    public function paginator(): PaginatorInterface
    {
        return $this->container->get(PaginatorInterface::class);
    }

    public function localeSwitcher(): LocaleSwitcher
    {
        return $this->container->get(LocaleSwitcher::class);
    }

    public function objectMapper(): ObjectMapperInterface
    {
       return $this->container->get(ObjectMapperInterface::class);
    }

    public function defaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function fileManager(): FileManager
    {
        return $this->container->get(FileManager::class);
    }

    public function fileValidator(): FileValidator
    {
        return $this->container->get(FileValidator::class);
    }

    public function imageCacheResolver(): ImageCacheResolver
    {
        return $this->container->get(ImageCacheResolver::class);
    }

    public function validator(): ValidatorInterface
    {
        return $this->container->get(ValidatorInterface::class);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EntityFilterer::class => '?' . EntityFilterer::class,
            PaginatorInterface::class => '?' . PaginatorInterface::class,
            LocaleSwitcher::class => '?' . LocaleSwitcher::class,
            'serializer' => '?' . SerializerInterface::class,
            ObjectMapperInterface::class => '?' . ObjectMapperInterface::class,
            FileManager::class => '?' . FileManager::class,
            FileValidator::class => new SubscribedService(
                type: FileValidator::class,
                nullable: true,
                attributes: new Autowire(service: 'lexio_admin.api.file_validator'),
            ),
            ImageCacheResolver::class => '?' . ImageCacheResolver::class,
            ValidatorInterface::class => '?' . ValidatorInterface::class,
            'security.authorization_checker' => '?' . AuthorizationCheckerInterface::class,
        ]);
    }

    /**
     * Renders a paginated listing of entities based on the provided query and output item.
     *
     * @param ApiQueryInterface $query The query object containing pagination and filtering parameters.
     * @param object $outputItem The output DTO instance to map each entity to.
     * @param list<string> $groups The serialization groups to apply.
     * @param class-string|null $entityFqcn The entity to list; defaults to the controller's entity.
     *
     * @return JsonResponse A JSON response containing the paginated data.
     */

    public function paginatedResponse(
        ApiQueryInterface $query,
        object            $outputItem,
        array             $groups = ['api:list'],
        ?string           $entityFqcn = null,
    ): JsonResponse
    {
        $entityFqcn ??= $this->getEntityFqcn();

        $this->switchLocale($query->getLocale());
        $this->validateSort($query, $entityFqcn);

        $itemsQuery = $this->filterService()->search(
            $entityFqcn,
            $query->getSearchPhrase(),
            $query->getSort(),
            $query->getOrder(),
            $query
        );

        $entities = $this->paginator()->paginate(
            $itemsQuery,
            $query->getPage(),
            $query->getLimit()
        );

        $data = [];
        foreach ($entities->getItems() as $entity) {
            $data[] = $this->objectMapper()->map($entity, clone $outputItem);
        }

        return $this->json(
            new PaginatedResponse($data, $query->getPage(), $query->getLimit(), $entities->getTotalItemCount()),
            Response::HTTP_OK,
            [],
            ['groups' => $groups],
        );
    }


    /**
     * Renders a full listing of entities based on the provided query and output item.
     *
     * @param ApiQueryInterface $query The query object containing filtering parameters.
     * @param object $outputItem The output DTO instance to map each entity to.
     *
     * @return JsonResponse A JSON response containing the full data.
     */
    public function fullListResponse(
        ApiQueryInterface $query,
        object $outputItem,
    ): JsonResponse
    {
        $this->switchLocale($query->getLocale());
        $this->validateSort($query, $this->getEntityFqcn());

        $entities = $this->filterService()->search(
            $this->getEntityFqcn(),
            $query->getSearchPhrase(),
            $query->getSort(),
            $query->getOrder(),
            $query
        )->getQuery()->getResult();


        $data = [];
        foreach ($entities as $entity) {
            $data[] = $this->objectMapper()->map($entity, clone $outputItem);
        }

        return $this->json(
            new FullListResponse($data, $query->getLimit(), count($entities)),
            Response::HTTP_OK,
            [],
            ['groups' => ['api:list']],
        );
    }

    /**
     * @param object $entity
     * @param object $outputItem
     * @param list<string> $groups
     * @return JsonResponse
     */
    public function viewResponse(object $entity, object $outputItem, array $groups = ['api:view'], ?string $locale = null): JsonResponse
    {
        $this->switchLocale($locale);

        $outputItem = $this->objectMapper()->map(source: $entity, target: $outputItem);

        return $this->json(
            $outputItem,
            Response::HTTP_OK,
            [],
            ['groups' => $groups],
        );
    }

    /**
     * Renders a JSON response for the creation of an entity.
     *
     * @param object $entity The newly created entity.
     * @param object $outputItem The output DTO instance to map the entity to.
     * @param string $showRoute #Route The name of the route to show the created entity.
     * @param string $showRouteParam #RouteParam The name of the route parameter that holds the entity
     *
     * @return JsonResponse A JSON response containing the created entity data and a Location header.
     */
    public function creationResponse(object $entity, ApiPayloadInterface $payload, object $outputItem, string $showRoute, string $showRouteParam): JsonResponse
    {
        $locale = $this->switchLocale($payload->getLocale());

        $mutatedEntity = $this->objectMapper()->map(source: $payload, target: $entity);

        if (method_exists($mutatedEntity, 'setTranslatableLocale')) {
            $mutatedEntity->setTranslatableLocale($locale);
        }

        $violations = $this->validator()->validate($mutatedEntity);

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations);
        }


        $this->manager()->persist($mutatedEntity);
        $this->manager()->flush();
        $this->refreshTranslation($mutatedEntity, $locale);

        $response = $this->viewResponse(
            $entity,
            $outputItem,
            ['api:view', 'api:create'],
            $payload->getLocale()
        );

        $identifier = method_exists($mutatedEntity, 'getId') ? $mutatedEntity->getId() : null;

        $response->setStatusCode(Response::HTTP_CREATED);
        $response->headers->set('Location', $this->generateUrl($showRoute, [$showRouteParam => $identifier]));

        return $response;
    }

    public function updateResponse(object $entity, ApiPayloadInterface $payload, object $outputItem): JsonResponse
    {
        $locale = $this->switchLocale($payload->getLocale());

        $mutatedEntity = $this->objectMapper()->map($payload, $entity);

        $violations = $this->validator()->validate($mutatedEntity);

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations);
        }

        $this->manager()->flush();
        $this->refreshTranslation($mutatedEntity, $locale);

        return $this->viewResponse($mutatedEntity,
            $outputItem,
            locale: $payload->getLocale()
        );
    }

    public function deleteResponse(object $entity): JsonResponse
    {
        try {
            $this->manager()->remove($entity);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete entity: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $this->manager()->flush();

        return $this->json(['result' => 'Entity deleted successfully'], Response::HTTP_OK);
    }

    /** @param class-string $entityFqcn */
    private function validateSort(ApiQueryInterface $query, string $entityFqcn): void
    {
        $sort = $query->getSort();

        if ($sort === null) {
            return;
        }

        $choices = $query::sortableFields() ?? $this->entitySortabilityPropertiesResolver->resolve($entityFqcn);
        $violations = $this->validator()->validate($sort, new Choice(choices: $choices));

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations, 'sort');
        }
    }

    protected function switchLocale(?string $locale): string
    {
        $request = $this->container->has('request_stack')
            ? $this->container->get('request_stack')->getCurrentRequest()
            : null;
        if ($request?->attributes->has('_locale')) {
            // The API locale subscriber has already selected and validated this locale.
            return $request->getLocale();
        }

        $locale ??= $this->defaultLocale();
        $violations = $this->validator()->validate($locale, new Choice(choices: $this->locales));

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations, 'locale');
        }

        $this->localeSwitcher()->setLocale($locale);

        return $locale;
    }

    private function refreshTranslation(object $entity, string $locale): void
    {
        if (method_exists($entity, 'setTranslatableLocale')) {
            $entity->setTranslatableLocale($locale);
            $this->manager()->refresh($entity);
        }
    }

    /**
     * Validates, stores and persists an uploaded file, then renders the JSON response for it.
     *
     * @param UploadedFile $uploadedFile The file received from the request.
     * @param FileEntityInterface $fileEntity The file entity to populate and persist.
     * @param object $outputItem The output DTO instance to map the entity to.
     * @param FileTypes $fileType The validation profile (image or document) to apply to the upload.
     * @param string $field The request field name carrying the file, used in validation errors.
     *
     * @return JsonResponse A JSON response containing the uploaded file data.
     *
     * @throws ApiValidationException When the uploaded file does not satisfy the constraints.
     */
    public function uploadResponse(
        UploadedFile        $uploadedFile,
        FileEntityInterface $fileEntity,
        object              $outputItem,
        FileTypes           $fileType = FileTypes::IMAGE,
        string              $field = 'file',
    ): JsonResponse
    {
        $violations = $this->fileValidator()->validate($fileType, $uploadedFile);

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations, $field);
        }

        $fileEntity = $this->fileManager()->uploadFile($uploadedFile, $fileEntity);

        $this->manager()->persist($fileEntity);
        $this->manager()->flush();

        return $this->json(
            $this->objectMapper()->map($fileEntity, $outputItem),
            Response::HTTP_CREATED,
            [],
            ['groups' => ['api:view']],
        );
    }
}
