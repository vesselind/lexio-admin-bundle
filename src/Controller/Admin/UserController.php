<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Lexio\AdminBundle\AdminCore\Bulk\BulkAction;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Fields\IdField;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\AdminCore\Tab\Tab;
use Lexio\AdminBundle\Contract\User\UserEntityInterface;
use Lexio\AdminBundle\Contract\User\UserInvitationInterface;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\Filter\BaseFilter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Reusable user administration with host-owned entities and forms.
 *
 * Host applications add their route prefix and access rule on the subclass.
 */
abstract class UserController extends BaseCrudController
{
    public function __construct(
        private readonly UserInvitationInterface $userInvitation,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /** @return class-string<UserEntityInterface> */
    abstract public function getEntityFqcn(): string;

    abstract public function userEntity(): UserEntityInterface;

    /** @return class-string<FormTypeInterface> */
    abstract public function getUserFormType(): string;

    /** @return class-string<FormTypeInterface> */
    abstract public function getPasswordFormType(): string;

    abstract public function getUserListingFilter(): BaseFilter;

    /** @return list<\BackedEnum> */
    abstract public function getRoleChoices(): array;

    protected function getListingTemplate(): string
    {
        return '@LexioAdmin/admin/user/listing.html.twig';
    }

    protected function getUpdateTemplate(): string
    {
        return '@LexioAdmin/admin/user/update.html.twig';
    }

    protected function getRolesTemplate(): string
    {
        return '@LexioAdmin/admin/user/roles.html.twig';
    }

    protected function getProfileTemplate(): string
    {
        return '@LexioAdmin/admin/user/profile.html.twig';
    }

    #[Route('', name: 'admin.user.index')]
    public function index(ListingContext $listingContext): Response
    {
        $listingContext
            ->setEntityFqcn($this->getEntityFqcn())
            ->addColumn('id', new IdField())
            ->addColumn('fullName', new TitleField())
            ->addColumn('email', new TitleField())
            ->addColumn('Actions', new DropdownActionsField())
            ->setFilter($this->getUserListingFilter())
            ->addBulkAction(new BulkAction('admin.user.bulk_delete', 'button.delete', 'fa6-solid:trash', 'danger'));

        return $this->renderListing($listingContext, $this->getListingTemplate());
    }

    #[Route('/create', name: 'admin.user.create')]
    public function create(Request $request, FormContext $formContext): Response
    {
        $user = $this->userEntity();
        $formContext->setEntityInstance($user)->setFormType($this->getUserFormType());

        $form = $this->createForm($this->getUserFormType(), $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $expiresAt = $this->userInvitation->invite($user);

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans(
                'user_created_from_admin',
                ['%validUntil%' => $expiresAt->format('d.m.Y H:i:s')],
                'messages',
            ));

            $formContext->getRequest()->attributes->set('_created_entity', $user);

            return $formContext->redirectOnSuccess();
        }

        return $this->renderCreate($user, $formContext);
    }

    #[Route('/bulk-delete', name: 'admin.user.bulk_delete', methods: ['POST'])]
    public function bulkDelete(BulkContext $bulkContext, Request $request): Response
    {
        foreach ($bulkContext->getEntities($this->getEntityFqcn()) as $entity) {
            $this->manager()->remove($entity);
        }

        $this->manager()->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('admin.user.index'));
    }

    #[Route('/{id}/update', name: 'admin.user.update')]
    public function update(int $id, FormContext $formContext): Response
    {
        $user = $this->findUser($id);
        $formContext
            ->setEntityInstance($user)
            ->setFormType($this->getUserFormType())
            ->disableLocalesTab()
            ->addTab(new Tab('tab.general', 'admin.user.update', ['id' => $user->getId()]))
            ->addTab(new Tab('tab.roles', 'admin.user.roles', ['id' => $user->getId()]));

        return $this->renderUpdate($user, $formContext, $this->getUpdateTemplate());
    }

    #[Route('/{id}/delete', name: 'admin.user.delete', methods: ['POST', 'GET'])]
    public function delete(int $id, Request $request): Response
    {
        return $this->renderDelete($this->findUser($id), $request);
    }

    #[Route('/{id}/roles', name: 'admin.user.roles')]
    public function roles(int $id, Request $request): Response
    {
        $user = $this->findUser($id);

        if ($request->isMethod('POST')) {
            $token = $request->request->all()['_token'] ?? null;

            if (!is_string($token) || !$this->isCsrfTokenValid('admin.user.roles.' . $id, $token)) {
                throw $this->createAccessDeniedException();
            }

            $submittedRoles = $request->request->all()['roles'] ?? [];
            $submittedRoles = is_array($submittedRoles) ? $submittedRoles : [];
            $allowedRoles = array_map(static fn (\BackedEnum $role): string => (string) $role->value, $this->getRoleChoices());
            $roles = array_values(array_unique(array_filter(
                $submittedRoles,
                static fn (mixed $role): bool => is_string($role) && in_array($role, $allowedRoles, true),
            )));
            $roles[] = 'ROLE_USER';

            $user->setRoles(array_values(array_unique($roles)));
            $this->manager()->persist($user);
            $this->manager()->flush();

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('user_roles_were_updated', [], 'admin'));

            return $this->redirectToRoute('admin.user.roles', ['id' => $user->getId()]);
        }

        return $this->render($this->getRolesTemplate(), [
            'user' => $user,
            'roles' => $this->getRoleChoices(),
        ]);
    }

    #[Route('/profile', name: 'admin.user.profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request): Response
    {
        $user = $this->getUser();

        if (!$user instanceof UserEntityInterface) {
            throw $this->createAccessDeniedException();
        }

        $profileForm = $this->createForm($this->getUserFormType(), $user, [
            'action' => $this->generateUrl('admin.user.profile'),
            'show_blocked' => false,
            'translation_domain' => 'form',
        ]);
        $passwordForm = $this->createForm($this->getPasswordFormType(), $user);

        if ($request->isMethod('POST')
            && $request->request->has($profileForm->getName())
            && $request->request->has($passwordForm->getName())) {
            throw new BadRequestHttpException();
        }

        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $this->manager()->flush();
            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('your_profile_updated_successfully', [], 'messages'));

            return $this->redirectToRoute('admin.user.profile');
        }

        if (!$profileForm->isSubmitted()) {
            $passwordForm->handleRequest($request);
        }

        if ($passwordForm->isSubmitted()) {
            $oldPassword = $passwordForm->get('oldPassword')->getData();
            $newPassword = $passwordForm->get('newPassword')->getData();

            if (!is_string($oldPassword) || !$this->passwordHasher->isPasswordValid($user, $oldPassword)) {
                $passwordForm->get('oldPassword')->addError(new FormError($this->translator()->trans('invalid_current_password', [], 'validators')));
            }

            if ($passwordForm->isValid()) {
                if (is_string($newPassword) && $newPassword !== '') {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
                }

                $this->manager()->flush();
                $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('your_profile_updated_successfully', [], 'messages'));

                return $this->redirectToRoute('admin.user.profile');
            }
        }

        return $this->render($this->getProfileTemplate(), [
            'user' => $user,
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
        ]);
    }

    private function findUser(int $id): UserEntityInterface
    {
        $user = $this->manager()->getRepository($this->getEntityFqcn())->find($id);

        if (!$user instanceof UserEntityInterface) {
            throw $this->createNotFoundException();
        }

        return $user;
    }
}
