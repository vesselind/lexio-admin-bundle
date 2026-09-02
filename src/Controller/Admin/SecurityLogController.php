<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

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
use Lexio\AdminBundle\Entity\SecurityLog;
use Lexio\AdminBundle\Filter\SecurityLogFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Provides reusable security-log administration.
 *
 * Host applications extend this controller to add the route prefix and access
 * rule used by their own application route loader.
 */
abstract class SecurityLogController extends BaseCrudController
{
    public function getEntityFqcn(): string
    {
        return SecurityLog::class;
    }

    #[Route('', name: 'admin.security_log.index')]
    public function index(ListingContext $listingContext, #[MapQueryString] SecurityLogFilter $filter): Response
    {
        $listingContext
            ->setEntityFqcn(SecurityLog::class)
            ->setFilter($filter)
            ->setShowCreateButton(false)
            ->addColumn('id', new IdField())
            ->addColumn('type', new EnumField())
            ->addColumn('ipAddress', new TitleField())
            ->addColumn('actingUser', new TitleField())
            ->addColumn('affectedUser', new TitleField())
            ->addColumn('createdAt', new DateTimeField())
            ->addColumn('Actions', new DropdownActionsField()
                ->addAction(DetailsAction::new('admin.security_log.details'))
            )
            ->addBulkAction(new BulkAction('admin.security_log.bulk_delete', 'button.delete', 'fa fa-trash', 'danger'));

        return $this->renderListing($listingContext);
    }

    #[Route('/{id}/update', name: 'admin.security_log.details')]
    public function details(SecurityLog $securityLog): Response
    {
        return $this->renderDetails($securityLog, '@LexioAdmin/admin/security_log/details.html.twig');
    }

    #[Route('/{id}/delete', name: 'admin.security_log.delete', methods: ['POST', 'GET'])]
    public function delete(SecurityLog $securityLog, Request $request): Response
    {
        return $this->renderDelete($securityLog, $request);
    }

    #[Route('/bulk-delete', name: 'admin.security_log.bulk_delete')]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities() as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect(
            $request->headers->get('referer') ?? $this->generateUrl('admin.security_log.index'),
        );
    }
}
