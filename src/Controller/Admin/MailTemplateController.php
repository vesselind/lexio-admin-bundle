<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Action\UpdateAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\BooleanField;
use Lexio\AdminBundle\AdminCore\Fields\DateTimeField;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Entity\MailTemplate;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Filter\MailTemplateFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/mail-template')]
#[IsGranted('ROLE_EDITOR')]
abstract class MailTemplateController extends BaseCrudController
{
    public function getEntityFqcn(): string
    {
        return MailTemplate::class;
    }

    public function mailFilter(): BaseFilter
    {
        return new MailTemplateFilter();

    }

    #[Route('', name: 'admin.mail_template.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn(MailTemplate::class)
            ->addColumn('id', new IdField())
            ->addColumn('name', new TitleField())
            ->addColumn('subject', new TitleField())
            ->addColumn('enabled', new BooleanField())
            ->addColumn('createdAt', new DateTimeField())
            ->addColumn('Actions', (new DropdownActionsField())
            ->addAction(UpdateAction::new('admin.mail_template.update'))
            )
            ->setFilter($this->mailFilter());

        return $this->renderListing($listingContext);
    }

    #[Route('/create', name: 'admin.mail_template.create')]
    public function create(Request $request, FormContext $formContext): Response
    {
        $entity = new MailTemplate();
        $formContext->setEntityInstance($entity);

        return $this->renderCreate($entity, $formContext);
    }

    #[Route('/bulk-delete', name: 'admin.mail_template.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.mail_template.index'));
    }

    #[Route('/{id}/update', name: 'admin.mail_template.update')]
    public function update(MailTemplate $mailTemplate, FormContext $formContext): Response
    {
        $formContext
            ->setEntityInstance($mailTemplate)
            ->disableLocalesTab();

        $placeholders = $mailTemplate->getPlaceholders();

        return $this->renderUpdate($mailTemplate, $formContext, 'admin/mail_template/form.html.twig', [
            'placeholders' => $placeholders,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin.mail_template.delete', methods: ['POST', 'GET'])]
    public function delete(MailTemplate $mailTemplate, Request $request): Response
    {
        return $this->renderDelete($mailTemplate, $request);
    }
}
