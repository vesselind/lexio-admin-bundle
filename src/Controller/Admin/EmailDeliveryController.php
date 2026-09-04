<?php

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Action\DeleteAction;
use Lexio\AdminBundle\AdminCore\Action\DetailsAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\DateTimeField;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Fields\EnumField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Entity\EmailDelivery;
use Lexio\AdminBundle\Filter\BaseFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Lexio\AdminBundle\Filter\EmailDeliveryFilter;

#[Route('/admin/email-delivery')]
abstract class EmailDeliveryController extends BaseCrudController
{
    public function getEntityFqcn(): string
    {
        return EmailDelivery::class;
    }

    public function emailDeliveryFilter(): BaseFilter
    {
        return new EmailDeliveryFilter();
    }

    #[Route('', name: 'admin.email_delivery.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn(EmailDelivery::class)
            ->setFilter($this->emailDeliveryFilter())
            ->addColumn('id', new IdField())
            ->addColumn('recipientEmail', new TitleField())
            ->addColumn('mailTemplate.name', new TitleField())
            ->addColumn('createdAt', new DateTimeField())
            ->addColumn('status', new EnumField())
            ->addColumn('Actions', new DropdownActionsField()
                ->addAction(DetailsAction::new('admin.email_delivery.details'))
                ->addAction(DeleteAction::new('admin.email_delivery.delete'))
            )
            ->setShowCreateButton(false)
            ->addBulkAction(new BulkAction('admin.email_delivery.bulk_delete', 'button.delete', 'fa fa-trash', 'danger'));

        return $this->renderListing($listingContext);
    }

    #[Route('/{id}/details', name: 'admin.email_delivery.details')]
    public function details(EmailDelivery $emailDelivery): Response
    {
        return $this->renderDetails($emailDelivery, 'admin/email_delivery/details.html.twig');
    }

    #[Route('/{id}/delete', name: 'admin.email_delivery.delete', methods: ['POST', 'GET'])]
    public function delete(EmailDelivery $emailDelivery, Request $request): Response
    {
        return $this->renderDelete($emailDelivery, $request);
    }

    #[Route('/bulk-delete', name: 'admin.email_delivery.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.email_delivery.index'));
    }
}
