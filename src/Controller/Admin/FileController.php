<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\DateTimeField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Enum\FileTypes;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\File\FileValidator;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Filter\FileFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/file')]
#[IsGranted('ROLE_EDITOR')]
abstract class FileController extends BaseCrudController
{

    /** @return class-string */
    abstract public function getEntityFqcn(): string;

    public function getFileListingFilter(): BaseFilter
    {
        return new FileFilter();
    }

    abstract public function fileEntity(): FileEntityInterface;


    #[Route('', name: 'admin.file.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn($this->getEntityFqcn())
            ->addColumn('id', new IdField())
            ->addColumn('name', new TitleField())
            ->addColumn('size', new TitleField())
            ->addColumn('createdAt', new DateTimeField())
            ->setFilter($this->getFileListingFilter())
            ->addBulkAction(new BulkAction('admin.file.bulk_delete', 'button.delete', 'fa fa-trash', 'danger'));

        return $this->renderListing($listingContext, '@LexioAdmin/admin/file/file_listing.html.twig');
    }

    #[Route('/bulk-delete', name: 'admin.file.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.file.index'));
    }

    #[Route('/upload', name: 'admin.file.upload', methods: ['POST'])]
    public function upload(Request $request, FileManager $fileManager, FileValidator $fileValidator): Response
    {
        $file = $request->files->get('file');

        $errors = $fileValidator->validate(FileTypes::DOCUMENT, $file);

        if ($errors->count() > 0) {
            $this->addFlash(Flash::ERROR->value, $errors->get(0)->getMessage());

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.file.index'));
        }

        $file = $fileManager->uploadFile($file, $this->fileEntity());

        $this->manager()->persist($file);
        $this->manager()->flush();

        $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('file_was_uploaded', [], 'LexioAdminBundle'));

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.file.index'));
    }

    #[Route('/{id}/download', name: 'admin.file.download')]
    public function download(int $id, FileManager $fileManager, Request $request): Response
    {
        $fileEntity = $this->manager()->getRepository(get_class($this->fileEntity()))->find($id);

        if (!$fileEntity) {
            $this->addFlash(Flash::ERROR->value, $this->translator()->trans('file_not_found', [], 'LexioAdminBundle'));

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.file.index'));
        }

        return $this->file($fileManager->getSystemPath($fileEntity));
    }

    #[Route('/{id}/delete', name: 'admin.file.delete')]
    public function delete(int $id, Request $request): Response
    {
        $fileEntity = $this->manager()->getRepository(get_class($this->fileEntity()))->find($id);

        if (!$fileEntity) {
            $this->addFlash(Flash::ERROR->value, $this->translator()->trans('file_not_found', [], 'LexioAdminBundle'));

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.file.index'));
        }

        return $this->renderDelete($fileEntity, $request);
    }
}
