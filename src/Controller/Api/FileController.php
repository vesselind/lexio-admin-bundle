<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Api;

use Lexio\AdminBundle\Api\Output\FileOutput;
use Lexio\AdminBundle\Api\Query\FileListQuery;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\AdminCore\Resolver\MapQueryStringValueResolver;
use Lexio\AdminBundle\Enum\FileTypes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

abstract class FileController extends BaseApiController
{
    /** @return class-string<FileEntityInterface> */
    abstract public function getEntityFqcn(): string;

    abstract public function fileEntity(): FileEntityInterface;

    #[Route('/list', name: 'api.v1.file.list', methods: ['GET'])]
    public function list(#[MapQueryString(resolver: MapQueryStringValueResolver::class, validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
                        FileListQuery $query
    ): JsonResponse
    {
        return $this->paginatedResponse($query, new FileOutput());
    }

    #[Route('/upload', name: 'api.v1.file.upload', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function upload(#[MapUploadedFile] UploadedFile $file): JsonResponse
    {
        $fileEntity = $this->fileEntity();

        $response = $this->uploadResponse($file, $fileEntity, new FileOutput(), FileTypes::DOCUMENT);

        $response->headers->set('Location', $this->generateUrl('api.v1.file.show', ['id' => $fileEntity->getId()]));

        return $response;
    }

    #[Route('/{id}/show', name: 'api.v1.file.show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $file = $this->findFile($id);

        return $this->viewResponse($file, new FileOutput());
    }

    #[Route('/{id}/delete', name: 'api.v1.file.delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITOR')]
    public function delete(int $id): JsonResponse
    {
        $file = $this->findFile($id);

        return $this->deleteResponse($file);
    }

    private function findFile(int $id): FileEntityInterface
    {
        $entity = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$entity instanceof FileEntityInterface) {
            throw $this->createNotFoundException();
        }

        return $entity;
    }
}
