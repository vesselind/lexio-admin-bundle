<?php

declare(strict_types=1);
namespace Lexio\AdminBundle\Controller\Admin;

use Knp\Component\Pager\PaginatorInterface;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Enum\FileTypes;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\File\FileValidator;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Filter\ImageFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class ImageController extends BaseCrudController
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /** @return class-string */
    abstract public function getEntityFqcn(): string;

    public function getImageListingFilter(): BaseFilter
    {
        return new ImageFilter();
    }

    abstract public function imageEntity(): FileEntityInterface;


    #[Route('', name: 'admin.image.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn($this->getEntityFqcn())
            ->setFilter($this->getImageListingFilter())
            ->setItemsPerPage(16);

        return $this->renderListing($listingContext, '@LexioAdmin/admin/image/image_gallery.html.twig');
    }

    #[Route('/upload', name: 'admin.image.upload', methods: ['POST'])]
    public function upload(Request $request, FileManager $fileManager, FileValidator $fileValidator): Response
    {
        $file = $request->files->get('file');

        $errors = $fileValidator->validate(FileTypes::IMAGE, $file);

        if ($errors->count() > 0) {
            $this->addFlash(Flash::ERROR->value, $errors->get(0)->getMessage());

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.image.index'));
        }

        $file = $fileManager->uploadFile($file, $this->imageEntity());

        $this->manager()->persist($file);
        $this->manager()->flush();

        $this->addFlash(Flash::SUCCESS->value, $this->translator->trans('image_was_uploaded', [], 'LexioAdminBundle'));

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.image.index'));
    }


    #[Route('/{id}/download', name: 'admin.image.download')]
    public function download(int $id, FileManager $fileManager, Request $request): Response
    {
        /** @var ?FileEntityInterface $imageEntity */
        $imageEntity = $this->manager()->getRepository(get_class($this->imageEntity()))->find($id);

        if ($imageEntity === null) {
            $this->addFlash(Flash::ERROR->value, $this->translator->trans('image_not_found', [], 'LexioAdminBundle'));

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.image.index'));
        }

        return $this->file($fileManager->getSystemPath($imageEntity));
    }

    #[Route('/bulk-delete', name: 'admin.image.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.image.index'));
    }

    #[Route('/{id}/delete', name: 'admin.image.delete')]
    public function delete(int $id, Request $request): Response
    {
        $imageEntity = $this->manager()->getRepository(get_class($this->imageEntity()))->find($id);

        if (!$imageEntity) {
            $this->addFlash(Flash::ERROR->value, $this->translator->trans('image_not_found', [], 'LexioAdminBundle'));

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.image.index'));
        }

        return $this->renderDelete($imageEntity, $request);
    }


    #[Route('/modal-gallery', name: 'admin.image.modal_gallery', priority: 3)]
    public function modalGallery(PaginatorInterface $paginator, Request $request): Response
    {
        $filter = new ImageFilter();
        $name = $request->query->get('name');

        if (\is_string($name)) {
            $filter->name = $name;
        }

        $images = $paginator->paginate(
            $this->filterService()->search($this->getEntityFqcn(), filter: $filter)->getQuery(),
            $request->query->getInt('page', 1),
            9,
        );

        return $this->render('@LexioAdmin/admin/image/modal_gallery.html.twig', [
            'images' => $images,
        ]);
    }

    #[Route('/modal-upload', name: 'admin.image.modal_upload', methods: ['POST'], priority: 3)]
    public function modalUpload(Request $request, FileManager $fileManager, FileValidator $fileValidator): Response
    {
        $file = $request->files->get('file');

        $errors = $fileValidator->validate(FileTypes::IMAGE, $file);

        if ($errors->count() > 0) {
            return $this->json(['message' => $errors->get(0)->getMessage()], 422);
        }

        $file = $fileManager->uploadFile($file, $this->imageEntity());

        $this->manager()->persist($file);
        $this->manager()->flush();

        return $this->json(['message' => 'Successful upload'], 200);
    }
}
