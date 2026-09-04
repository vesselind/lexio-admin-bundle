<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Action\DeleteAction;
use Lexio\AdminBundle\AdminCore\Action\DetailsAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\DateTimeField;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Contract\Notification\NotificationEntityInterface;
use Lexio\AdminBundle\Contract\Notification\NotificationUserInterface;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\Filter\BaseFilter;
use Lexio\AdminBundle\Filter\SystemNotificationFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/system-notification')]
abstract class SystemNotificationController extends BaseCrudController
{
    /**
     * @return class-string
     */
    abstract public function getEntityFqcn(): string;

    abstract public function notificationEntity(): NotificationEntityInterface;

    public function systemNotificationFilter(): SystemNotificationFilter
    {
        return new SystemNotificationFilter();
    }


    #[Route('', name: 'admin.system_notification.index')]
    public function index(ListingContext $listingContext): Response
    {
        $filter = $this->systemNotificationFilter();

        $actions = new DropdownActionsField()
            ->addAction(DetailsAction::new('admin.system_notification.view'))
            ->addAction(DeleteAction::new('admin.system_notification.delete'));

        $listingContext
            ->setEntityFqcn($this->getEntityFqcn())
            ->addColumn('id', new IdField())
            ->addColumn('title', new TitleField())
            ->addColumn('createdAt', new DateTimeField(dateOnly: false))
            ->addColumn('Actions', $actions)
            ->setFilter($filter)
            ->setShowCreateButton(false)
            ->addBulkAction(new BulkAction('admin.system_notification.bulk_delete', 'button.delete', 'fa fa-trash', 'danger'));

        return $this->renderListing($listingContext);
    }

    protected function enforceListingFilter(BaseFilter $filter): void
    {
        if (!$filter instanceof SystemNotificationFilter) {
            throw new \LogicException('System notification listings require a SystemNotificationFilter.');
        }

        $filter->userEmail = $this->currentNotificationUser()->getEmail();
    }

    #[Route('/create', name: 'admin.system_notification.create')]
    public function create(Request $request, FormContext $formContext): Response
    {
        $entity = $this->notificationEntity();
        $formContext->setEntityInstance($entity);

        return $this->renderCreate($entity, $formContext);
    }

    #[Route('/{id}/update', name: 'admin.system_notification.update')]
    public function update(int $id, FormContext $formContext): Response
    {
        $entity = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$entity) {
            throw $this->createNotFoundException();
        }

        $formContext->setEntityInstance($entity);

        return $this->renderUpdate($entity, $formContext);
    }

    #[Route('/{id}/view', name: 'admin.system_notification.view')]
    public function view(int $id): Response
    {
        $notificationEntity = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$notificationEntity instanceof NotificationEntityInterface) {
            throw $this->createNotFoundException();
        }

        $this->assertOwnedByCurrentUser($notificationEntity);

        $notificationEntity->markAsRead();
        $this->manager()->flush();

        return $this->render('@LexioAdmin/admin/system_notification/_view.html.twig', [
            'notification' => $notificationEntity
        ]);
    }

    #[Route('/{id}/delete', name: 'admin.system_notification.delete', methods: ['POST', 'GET'])]
    public function delete(int $id, Request $request): Response
    {
        $notificationEntity = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$notificationEntity instanceof NotificationEntityInterface) {
            $this->addFlash(Flash::ERROR->value, $this->translator()->trans('notification_entity_not_found', [], 'LexioAdminBundle'));

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.system_notification.index'));
        }

        $this->assertOwnedByCurrentUser($notificationEntity);

        return $this->renderDelete($notificationEntity, $request);
    }

    #[Route('/bulk-delete', name: 'admin.system_notification.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        $entities = $bulkContext->getEntities($this->getEntityFqcn());

        foreach ($entities as $entity) {
            if (!$entity instanceof NotificationEntityInterface) {
                throw new \LogicException('System notification bulk actions require notification entities.');
            }

            $this->assertOwnedByCurrentUser($entity);
        }

        foreach ($entities as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.system_notification.index'));
    }

    private function currentNotificationUser(): NotificationUserInterface
    {
        $user = $this->getUser();

        if (!$user instanceof NotificationUserInterface) {
            throw $this->createAccessDeniedException('The current user cannot access notifications.');
        }

        return $user;
    }

    private function assertOwnedByCurrentUser(NotificationEntityInterface $notification): void
    {
        $currentUserId = $this->currentNotificationUser()->getId();

        if ($currentUserId === null || $notification->getUser()?->getId() !== $currentUserId) {
            throw $this->createAccessDeniedException();
        }
    }
}
