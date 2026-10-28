<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Api\Output\ImageOutput;
use Lexio\AdminBundle\Api\Query\ImageListQuery;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Exception\ApiValidationException;
use Lexio\AdminBundle\AdminCore\Resolver\MapQueryStringValueResolver;
use Lexio\AdminBundle\Enum\FileTypes;
use Lexio\AdminBundle\File\ImageCacheConfig;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

abstract class ImageController extends BaseApiController
{
    private const int MAX_AS_SIZE_DIMENSION = 4000;

    /** @return class-string<ImageEntityInterface> */
    abstract public function getEntityFqcn(): string;

    abstract public function imageEntity(): ImageEntityInterface;

    #[Route('/list', name: 'api.v1.image.list', methods: ['GET'])]
    public function list(#[MapQueryString(resolver: MapQueryStringValueResolver::class, validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
                        ImageListQuery $query
    ): JsonResponse
    {
        return $this->paginatedResponse($query, new ImageOutput());
    }

    #[Route('/upload', name: 'api.v1.image.upload', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function upload(#[MapUploadedFile] UploadedFile $file): JsonResponse
    {
        $image = $this->imageEntity();

        $response = $this->uploadResponse($file, $image, new ImageOutput(), FileTypes::IMAGE);

        $response->headers->set('Location', $this->generateUrl('api.v1.image.show', ['id' => $image->getId()]));

        return $response;
    }

    #[Route('/{id}/show', name: 'api.v1.image.show', methods: ['GET'])]
    public function show(int $id, #[MapQueryParameter] ?string $asSize = null): JsonResponse
    {
        $image = $this->findImage($id);

        $output = new ImageOutput();

        $this->objectMapper()->map(source: $image, target: $output);

        $asSize = $asSize === null ? null : trim($asSize);

        if ($asSize !== null && $asSize !== '') {
            $output->url = $this->sizedUrl($image, $asSize);
        }

        return $this->json(
            $output,
            Response::HTTP_OK,
            [],
            ['groups' => ['api:view']],
        );
    }

    #[Route('/{id}/delete', name: 'api.v1.image.delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITOR')]
    public function delete(int $id): JsonResponse
    {
        $image = $this->findImage($id);

        return $this->deleteResponse($image);
    }

    /**
     * Resolves the cached/resized image through the bundle's ImageCacheResolver
     * (same service the `<twig:Img>` component's `|image` filter uses), then lets
     * FileManager generate the absolute URL for the returned web path.
     */
    private function sizedUrl(ImageEntityInterface $image, string $asSize): string
    {
        $cachedWebPath = $this->imageCacheResolver()->resolveImage($image, $this->imageSizeConfig($asSize));

        return $this->fileManager()->getAbsoluteUrlForPath($cachedWebPath);
    }

    /**
     * Parses `asSize` in the formats "WIDTHxHEIGHT", "cover_WIDTHxHEIGHT" or "scale_WIDTHxHEIGHT".
     */
    private function imageSizeConfig(string $asSize): ImageCacheConfig
    {
        if (1 !== preg_match('/^(?:(cover|scale)_)?(\d{1,4})x(\d{1,4})$/i', $asSize, $matches)) {
            throw ApiValidationException::field(
                'asSize',
                'The size must use the format "WIDTHxHEIGHT", optionally prefixed with "cover_" or "scale_".'
            );
        }

        $width = (int) $matches[2];
        $height = (int) $matches[3];

        if ($width < 1 || $height < 1 || $width > self::MAX_AS_SIZE_DIMENSION || $height > self::MAX_AS_SIZE_DIMENSION) {
            throw ApiValidationException::field('asSize', 'The size dimensions must be between 1 and 4000 pixels.');
        }

        return new ImageCacheConfig(
            (string) $width,
            (string) $height,
            $matches[1] !== '' ? strtolower($matches[1]) : ImageCacheConfig::FILTER_COVER,
        );
    }

    private function findImage(int $id): ImageEntityInterface
    {
        $entity = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$entity instanceof ImageEntityInterface) {
            throw $this->createNotFoundException();
        }

        return $entity;
    }
}
