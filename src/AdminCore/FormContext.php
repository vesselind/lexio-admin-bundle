<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore;

use Lexio\AdminBundle\AdminCore\Action\Action;
use Lexio\AdminBundle\AdminCore\Fields\DropdownActionsField;
use Lexio\AdminBundle\AdminCore\Tab\Tab;
use Lexio\AdminBundle\AdminCore\Tab\TabInterface;
use Lexio\AdminBundle\Http\TurboRedirectResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormContext
{
    public const array MODAL_FRAMES = ['base-modal-body'];

    private ?object $entityInstance  = null;

    /** @var class-string|null */
    private ?string $entityFqcn      = null;
    private ?string $redirectUrl     = null;
    private string  $redirectTargetFrame = '_top';
    private ?string $pageTitle       = null;
    private ?string $formType        = null;

    /** @var array<string, mixed> */
    private array   $formOptions     = [];

    /** @var ArrayCollection<int, TabInterface> */
    private ArrayCollection $tabs;
    private DropdownActionsField $dropdownActionsField;
    private bool    $showLocalesTab  = true;
    private ?string $containerClass  = null;

    /**
     * @param array<int, string> $locales
     */
    public function __construct(
        private readonly RequestStack        $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly AdminUrlGenerator   $adminUrlGenerator,
        private readonly array               $locales,
        private readonly string              $defaultLocale,
        private readonly string              $translationDomain,
    ) {
        $this->tabs                = new ArrayCollection();
        $this->dropdownActionsField = new DropdownActionsField();
    }

    public function setEntityInstance(object $entityInstance): static
    {
        if (!$this->entityInstance) {
            $this->entityInstance = $entityInstance;
        }

        return $this;
    }

    public function getEntityInstance(): object
    {
        $this->assertEntityInstanceNotNull();

        if ($this->entityInstance === null) {
            throw new \LogicException('[FormContext] Entity instance is not set.');
        }

        return $this->entityInstance;
    }

    /**
     * @return class-string|null
     */
    public function getEntityFqcn(): ?string
    {
        if (!$this->entityFqcn) {
            $this->entityFqcn = $this->getEntityInstance()::class;
        }

        return $this->entityFqcn;
    }

    public function setRedirect(string $url, string $targetFrame = '_top'): static
    {
        $this->redirectUrl         = $url;
        $this->redirectTargetFrame = $targetFrame;

        return $this;
    }

    public function getRedirect(): ?string
    {
        return $this->redirectUrl;
    }

    public function setPageTitle(string $title): static
    {
        $this->pageTitle = $title;

        return $this;
    }

    public function getPageTitle(): string
    {
        if ($this->pageTitle) {
            return $this->translator->trans($this->pageTitle, [], $this->translationDomain);
        }

        return $this->translator->trans(
            $this->getRequest()->attributes->get('_route'),
            [],
            $this->translationDomain
        );
    }

    /**
     * @return array<int, string>
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    public function disableLocalesTab(): static
    {
        $this->showLocalesTab = false;

        return $this;
    }

    public function showLocalesTab(): bool
    {
        return $this->showLocalesTab;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getCurrentLocale(): string
    {
        return $this->getRequest()->query->get('language', $this->defaultLocale);
    }

    public function setFormType(string $formType): static
    {
        $this->formType = $formType;

        return $this;
    }

    public function getFormType(): string
    {
        if ($this->formType) {
            return $this->formType;
        }

        return str_replace('Entity', 'Form', $this->getEntityFqcnOrFail()) . 'Type';
    }

    /**
     * @param array<string, mixed> $formOptions
     */
    public function setFormOptions(array $formOptions): static
    {
        $this->formOptions = $formOptions;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        $defaultOptions = [
            'action' => $this->formOptions['action'] ?? $this->getRequest()->getUri(),
        ];

        if (!isset($this->formOptions['attr']['data-turbo-frame'])) {
            $defaultOptions['attr'] = [
                'data-turbo-frame' => $this->isModalRequest() ? '_self' : '_top',
            ];
        }

        return array_merge($defaultOptions, $this->formOptions);
    }

    public function addTab(TabInterface $tab): static
    {
        if (!$this->tabs->contains($tab)) {
            $this->tabs->add($tab);
        }

        return $this;
    }

    /**
     * @return Collection<int, TabInterface>
     */
    public function tabs(): Collection
    {
        foreach ($this->tabs as $tab) {
            if ($tab instanceof Tab && $this->entityInstance !== null) {
                $tab->setEntityInstance($this->entityInstance);
            }
        }

        return $this->tabs;
    }

    public function addAction(Action $action): static
    {
        $this->dropdownActionsField->addAction($action);

        return $this;
    }

    public function getDropdownActionsField(): DropdownActionsField
    {
        $this->dropdownActionsField->setEntityInstance($this->getEntityInstance());

        return $this->dropdownActionsField;
    }

    public function links(): AdminUrlGenerator
    {
        return $this->adminUrlGenerator
            ->setEntityFqcn($this->getEntityFqcnOrFail())
            ->setEntityInstance($this->getEntityInstance());
    }

    public function getRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \LogicException('[FormContext] Request is not available outside an HTTP context.');
        }

        return $request;
    }

    public function isModalRequest(): bool
    {
        return \in_array(
            $this->getRequest()->headers->get('Turbo-Frame'),
            self::MODAL_FRAMES,
            true
        );
    }

    public function redirectOnSuccess(): Response
    {
        if ($this->isModalRequest()) {
            return new RedirectResponse($this->getRequest()->getUri());
        }

        if ($this->redirectTargetFrame !== '_top' && $this->getRedirect()) {
            return new TurboRedirectResponse(
                $this->getRedirect(),
                targetFrame: $this->redirectTargetFrame
            );
        }

        return new RedirectResponse($this->links()->updateLink());
    }

    public function setContainerClass(?string $containerClass): static
    {
        $this->containerClass = $containerClass;

        return $this;
    }

    public function getContainerClass(): ?string
    {
        return $this->containerClass ?? 'col-xl-10 col-lg-12';
    }

    private function assertEntityInstanceNotNull(): void
    {
        if (!$this->entityInstance) {
            throw new \LogicException('[FormContext] Entity instance is not set. Call setEntityInstance() first.');
        }
    }

    /**
     * @return class-string
     */
    private function getEntityFqcnOrFail(): string
    {
        $entityFqcn = $this->getEntityFqcn();

        if ($entityFqcn === null) {
            throw new \LogicException('[FormContext] Entity FQCN is not set.');
        }

        return $entityFqcn;
    }
}

