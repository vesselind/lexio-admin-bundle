<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\AdminCore\AdminUrlGenerator;
use Lexio\AdminBundle\AdminCore\Breadcrumbs\AdminBreadcrumbs;
use Lexio\AdminBundle\AdminCore\FormContext;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use Lexio\AdminBundle\Component\ConfirmationModal;
use Lexio\AdminBundle\Controller\Admin\ModalContextAwareTrait;
use Lexio\AdminBundle\Enum\Flash;
use Lexio\AdminBundle\Event\AdminLifecycle\AfterEntityPersisted;
use Lexio\AdminBundle\Event\AdminLifecycle\AfterEntityUpdated;
use Lexio\AdminBundle\Event\AdminLifecycle\BeforeEntityPersisted;
use Lexio\AdminBundle\Event\AdminLifecycle\BeforeEntityUpdated;
use Lexio\AdminBundle\Form\SeoType;
use Lexio\AdminBundle\Service\EntityFilterer;
use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract base controller for all admin CRUD sections.
 *
 * Concrete controllers extend this class and implement {@see getEntityFqcn()}.
 * Concrete actions must initialize their contexts explicitly: listing actions call
 * {@see ListingContext::setEntityFqcn()} before configuring columns, while form
 * actions call {@see FormContext::setEntityInstance()} before using a render helper.
 * Helper methods (renderListing, renderCreate, renderUpdate, renderDelete, renderDetails, renderSeo)
 * handle all common CRUD actions with lifecycle events, flash messages, breadcrumbs and redirects.
 *
 * Services are resolved lazily via the ServiceSubscriber pattern to avoid
 * injecting unused dependencies on every request.
 */
abstract class BaseCrudController extends AbstractController
{
    use ModalContextAwareTrait;

    // ─── Listing ─────────────────────────────────────────────────────────────

    /**
     * @param ListingContext $listingContext
     * @param string|null $view #Template
     * @param array<string, mixed> $params
     * @return Response
     */
    public function renderListing(
        ListingContext $listingContext,
        ?string        $view = null,
        array          $params = [],
    ): Response
    {
        $request = $listingContext->getRequest();

        $filtersForm = null;
        if ($listingContext->getFilter()) {
            $filtersForm = $this->createForm(
                $listingContext->getFilterFormType(),
                $listingContext->getFilter(),
                [
                    'data_class' => get_class($listingContext->getFilter()),
                    'method' => 'GET',
                    'action' => $this->adminUrlGenerator()->indexLink(),
                ]
            );

            $filtersForm->handleRequest($request);
        }

        $itemsQuery = $this->filterService()->search(
            $this->getEntityFqcn(),
            $request->query->get('q', ''),
            $request->query->get('sort'),
            $request->query->get('order'),
            $listingContext->getFilter(),
        );

        $items = $listingContext->paginator->paginate(
            $itemsQuery,
            $request->query->getInt('page', 1),
            $listingContext->getItemsPerPage()
        );

        if ($this->breadcrumbs()->hasItems() === false) {
            $this->breadcrumbs()->forIndex($this->getIndexTitle());
        }

        return $this->render($view ?? '@LexioAdmin/admin/base_crud/listing.html.twig', array_merge($params, [
            'title' => $this->getIndexTitle(),
            'listingContext' => $listingContext,
            'items' => $items,
            'entityFqcn' => $this->getEntityFqcn(),
            'adminUrlGenerator' => $this->adminUrlGenerator(),
            'filtersForm' => $filtersForm,
        ]));
    }

    // ─── Create ──────────────────────────────────────────────────────────────

    /**
     * @param string|null $view #Template
     * @param array<string, mixed> $params
     */
    public function renderCreate(
        object      $entity,
        FormContext $formContext,
        ?string     $view = null,
        array       $params = [],
    ): Response
    {
        $form = $this->createForm(
            $formContext->getFormType(),
            $entity,
            [
                'action' => $formContext->getRequest()->getUri(),
                'attr' => [
                    'data-turbo-frame' => $formContext->isModalRequest() ? 'data-turbo-modal' : '_top',
                ],
            ]
        );

        $form->handleRequest($formContext->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher()->dispatch(
                new BeforeEntityPersisted($entity, $formContext->getCurrentLocale())
            );

            $this->manager()->persist($entity);
            $this->manager()->flush();

            $this->eventDispatcher()->dispatch(
                new AfterEntityPersisted($entity, $formContext->getCurrentLocale())
            );

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('message.item_created', [], $this->translationDomain()));

            $formContext->getRequest()->attributes->set('_created_entity', $entity);

            return $formContext->redirectOnSuccess();
        }

        if ($this->breadcrumbs()->hasItems() === false) {
            $this->breadcrumbs()->forPage($this->getIndexTitle(), $formContext->getPageTitle());
        }

        return $this->render($view ?? '@LexioAdmin/admin/base_crud/form.html.twig', array_merge($params, [
            'formContext' => $formContext,
            'form' => $form,
            'adminUrlGenerator' => $this->adminUrlGenerator(),
        ]));
    }

    // ─── Update ──────────────────────────────────────────────────────────────

    /**
     * @param string|null $view #Template
     * @param array<string, mixed> $params
     */
    public function renderUpdate(
        object      $entity,
        FormContext $formContext,
        ?string     $view = null,
        array       $params = [],
    ): Response
    {
        if (method_exists($entity, 'setTranslatableLocale')) {
            $entity->setTranslatableLocale($formContext->getCurrentLocale());
            $this->manager()->refresh($entity);
        }

        $form = $this->createForm($formContext->getFormType(), $entity, $formContext->getFormOptions());

        $form->handleRequest($formContext->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher()->dispatch(
                new BeforeEntityUpdated($entity, $formContext->getCurrentLocale())
            );

            $this->manager()->flush();

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('message.item_updated', [], $this->translationDomain()));

            $this->eventDispatcher()->dispatch(
                new AfterEntityUpdated($entity, $formContext->getCurrentLocale())
            );

            return $formContext->redirectOnSuccess();
        }

        if ($this->breadcrumbs()->hasItems() === false) {
            $this->breadcrumbs()->forPage($this->getIndexTitle(), $formContext->getPageTitle());
        }

        return $this->render($view ?? '@LexioAdmin/admin/base_crud/form.html.twig', array_merge($params, [
            'formContext' => $formContext,
            'form' => $form,
            'adminUrlGenerator' => $this->adminUrlGenerator(),
        ]));
    }

    // ─── Delete ──────────────────────────────────────────────────────────────

    public function renderDelete(object $entity, Request $request): Response
    {
        $subjectId = $request->getSession()->get(ConfirmationModal::CONFIRMED_SESSION_KEY);

        if ($subjectId !== $entity->getId()) {
            throw new \RuntimeException(
                'Confirmation modal session key does not match entity ID. Deletion aborted.'
            );
        }

        $request->getSession()->remove(ConfirmationModal::CONFIRMED_SESSION_KEY);

        try {
            $this->manager()->remove($entity);
            $this->manager()->flush();

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('message.item_deleted', [], $this->translationDomain()));
        } catch (\Exception $e) {
            $this->addFlash(
                Flash::ERROR->value,
                $this->translator()->trans('message.item_could_not_be_deleted', ['%message%' => $e->getMessage()], $this->translationDomain())
            );
        }

        return $this->redirect($request->headers->get('referer') ?? $this->adminUrlGenerator()->indexLink());
    }

    // ─── Details ─────────────────────────────────────────────────────────────

    /**
     * @param string|null $view #Template
     * @param array<string, mixed> $params
     */
    public function renderDetails(
        object  $entity,
        ?string $view = null,
        array   $params = [],
    ): Response
    {
        $pageTitle = $this->translator()->trans(
            sprintf('admin.%s.details', $this->getSnakeEntityName()),
            [],
            $this->translationDomain()
        );

        if ($this->breadcrumbs()->hasItems() === false) {
            $this->breadcrumbs()->forPage($this->getIndexTitle(), $pageTitle);
        }

        return $this->render($view ?? '@LexioAdmin/admin/base_crud/details.html.twig', array_merge($params, [
            'pageTitle' => $pageTitle,
            'entity' => $entity,
            'adminUrlGenerator' => $this->adminUrlGenerator(),
        ]));
    }

    // ─── SEO ─────────────────────────────────────────────────────────────────

    /**
     * @param string|null $view #Template
     * @param array<string, mixed> $params
     */
    public function renderSeo(
        object      $entity,
        FormContext $formContext,
        ?string     $view = null,
        array       $params = [],
    ): Response
    {
        $formContext->setFormType(SeoType::class)
            ->disableLocalesTab();

        $form = $this->createForm($formContext->getFormType(), $entity, [
            'data_class' => $this->getEntityFqcn(),
            'action' => $formContext->getRequest()->getUri(),
        ]);

        $form->handleRequest($formContext->getRequest());

        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager()->flush();

            $this->addFlash(Flash::SUCCESS->value, $this->translator()->trans('message.item_updated', [], $this->translationDomain()));

            return $this->redirect($formContext->getRequest()->getUri());
        }

        return $this->render($view ?? '@LexioAdmin/admin/base_crud/seo.html.twig', array_merge($params, [
            'formContext' => $formContext,
            'form' => $form,
            'adminUrlGenerator' => $this->adminUrlGenerator(),
        ]));
    }

    // ─── Abstract / override points ──────────────────────────────────────────

    /**
     * @return class-string
     */
    abstract public function getEntityFqcn(): string;

    protected function getSnakeEntityName(): string
    {
        return AdminUtils::classNameToSnake($this->getEntityFqcn());
    }

    protected function getIndexTitle(): string
    {
        return $this->translator()->trans('admin.' . $this->getSnakeEntityName() . '.index', [], $this->translationDomain());
    }

    // ─── Service accessors (ServiceSubscriber pattern) ────────────────────────

    public function manager(): EntityManagerInterface
    {
        return $this->container->get('doctrine')->getManager();
    }

    public function filterService(): EntityFilterer
    {
        return $this->container->get(EntityFilterer::class);
    }

    public function translator(): TranslatorInterface
    {
        return $this->container->get('translator');
    }

    protected function translationDomain(): string
    {
        $domain = $this->container->get('parameter_bag')->get('lexio_admin.ui.translation_domain');
        if (!is_string($domain) || '' === $domain) {
            throw new \LogicException('The admin UI translation domain must be a non-empty string.');
        }

        return $domain;
    }

    public function breadcrumbs(): AdminBreadcrumbs
    {
        return $this->container->get(AdminBreadcrumbs::class)->setEntityFqcn($this->getEntityFqcn());
    }

    public function adminUrlGenerator(): AdminUrlGenerator
    {
        return $this->container->get(AdminUrlGenerator::class)->setEntityFqcn($this->getEntityFqcn());
    }

    public function eventDispatcher(): EventDispatcherInterface
    {
        return $this->container->get('event_dispatcher');
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => '?' . ManagerRegistry::class,
            'translator' => '?' . TranslatorInterface::class,
            EntityFilterer::class => '?' . EntityFilterer::class,
            AdminBreadcrumbs::class => '?' . AdminBreadcrumbs::class,
            AdminUrlGenerator::class => '?' . AdminUrlGenerator::class,
            'event_dispatcher' => '?' . EventDispatcherInterface::class,
            'router' => '?' . RouterInterface::class,
            'form.factory' => '?' . FormFactoryInterface::class,
            'security.token_storage' => '?' . TokenStorageInterface::class,
            'security.csrf.token_manager' => '?' . CsrfTokenManagerInterface::class,
            'parameter_bag' => '?' . ContainerBagInterface::class,
        ]);
    }
}

