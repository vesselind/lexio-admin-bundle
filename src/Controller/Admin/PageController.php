<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Action\UpdateAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\DateTimeField;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\AdminCore\Tab\Tab;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\Filter\PageFilter;
use Lexio\AdminBundle\Form\PageObjectType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides the reusable administration of Page entities and their ContentItems.
 *
 * Host applications extend this controller to add the route prefix and access
 * rule used by their own application route loader.
 */
abstract class PageController extends BaseCrudController
{
    public function getEntityFqcn(): string
    {
        return Page::class;
    }

    #[Route('', name: 'admin.page.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn(Page::class)
            ->addColumn('id', new IdField())
            ->addColumn('title', new TitleField())
            ->addColumn('name', new TitleField())
            ->addColumn('createdAt', new DateTimeField())
            ->addColumn('Actions', (new DropdownActionsField())
                ->addAction(UpdateAction::new('admin.page.update'))
            )
            ->setFilter(new PageFilter())
            ->setShowCreateButton(false)
            ->addBulkAction(new BulkAction('admin.page.bulk_delete', 'button.delete', 'fa fa-trash', 'danger'));

        return $this->renderListing($listingContext);
    }

    #[Route('/create', name: 'admin.page.create')]
    public function create(Request $request, FormContext $formContext): Response
    {
        $entity = new Page();
        $formContext->setEntityInstance($entity);

        return $this->renderCreate($entity, $formContext);
    }

    #[Route('/bulk-delete', name: 'admin.page.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect(
            $request->headers->get('referer') ?? $this->generateUrl('admin.page.index'),
        );
    }

    #[Route('/{id}/update', name: 'admin.page.update')]
    public function update(Page $page, FormContext $formContext, PageAdministrationInterface $pageManager): Response
    {
        $formContext->setEntityInstance($page);

        $pageName = $page->getName();
        $pageObject = null === $pageName
            ? null
            : $pageManager->getPageObject($pageName, $formContext->getCurrentLocale());

        if (null === $pageObject) {
            throw new \RuntimeException('Page object not found');
        }

        $formContext->addTab(new Tab('tab.general', 'admin.page.update', ['id' => $page->getId()]));

        $form = $this->createForm(PageObjectType::class, $pageObject, [
            'data_class' => $pageObject::class,
            'translation_domain' => $this->translationDomain(),
            'action' => $formContext->getRequest()->getUri(),
            'attr' => ['data-turbo-frame' => $formContext->isModalRequest() ? '_self' : '_top'],
        ]);

        $form->handleRequest($formContext->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $pageManager->createOrUpdatePage($pageObject, $formContext->getCurrentLocale());

            $this->addFlash(
                Flash::SUCCESS->value,
                $this->translator()->trans('page_updated', [], $this->translationDomain()),
            );

            return $this->redirectToRoute('admin.page.update', ['id' => $page->getId()]);
        }

        $this->breadcrumbs()->forPage(
            $this->getIndexTitle(),
            $formContext->getPageTitle(),
        );

        return $this->render('@LexioAdmin/admin/page/form.html.twig', [
            'formContext' => $formContext,
            'form' => $form,
            'item' => $pageObject,
            'adminUrlGenerator' => $this->adminUrlGenerator(),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin.page.delete', methods: ['POST', 'GET'])]
    public function delete(Page $page, Request $request): Response
    {
        return $this->renderDelete($page, $request);
    }
}
