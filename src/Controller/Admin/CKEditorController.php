<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Enum\FileTypes;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\File\FileValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ckeditor')]
abstract class CKEditorController extends BaseController
{
    abstract public function imageEntity(): ImageEntityInterface;

    #[Route('/browse-images', name: 'admin.ckeditor.upload')]
    public function upload(Request $request, FileManager $fileManager, FileValidator $fileValidator): JsonResponse
    {
        $uploadedFile = $request->files->get('upload');
        $errors = $fileValidator->validate(FileTypes::IMAGE, $uploadedFile);

        if ($errors->count() > 0) {
            $errorsMessage = '';

            foreach ($errors as $error) {
                $errorsMessage .= $error->getMessage() . "\n";
            }

            return new JsonResponse([
                'uploaded' => 0,
                'error' => [
                    'message' => $errorsMessage,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $image = $fileManager->uploadFile($uploadedFile, $this->imageEntity());
        $this->manager()->persist($image);
        $this->manager()->flush();

        return new JsonResponse([
            'url' => $image->getFilePath(),
        ], Response::HTTP_CREATED);
    }
}
