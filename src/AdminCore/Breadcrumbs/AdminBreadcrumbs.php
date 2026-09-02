<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Breadcrumbs;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Lexio\AdminBundle\AdminCore\AdminUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class AdminBreadcrumbs
{
    public function __construct(
        private Breadcrumbs           $breadcrumbs,
        private AdminUrlGenerator     $adminUrlGenerator,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface   $translator,
        private string                $translationDomain,
    )
    {
    }

    /**
     * @param class-string $entityFqcn
     * @return $this
     */
    public function setEntityFqcn(string $entityFqcn): static
    {
        $this->adminUrlGenerator->setEntityFqcn($entityFqcn);

        return $this;
    }

    public function forHome(): self
    {
        $this->breadcrumbs->addItem(
            $this->translator->trans('Home', [], $this->translationDomain),
            $this->adminUrlGenerator->getHomeLink()
        );

        return $this;
    }

    public function forIndex(string $indexTitle): static
    {
        $this->forHome();

        $this->breadcrumbs->addItem($indexTitle, $this->adminUrlGenerator->indexLink());

        return $this;
    }

    public function forPage(string $indexTitle, string $pageTitle): static
    {
        $this->forIndex($indexTitle);

        $this->breadcrumbs->addItem($pageTitle);

        return $this;
    }

    /**
     * @param string $title #Translation
     * @param string $route #Route
     * @param array<string, mixed> $routeParams #Route Parameters
     * @return static
     */
    public function addItem(string $title, string $route, array $routeParams = []): static
    {
        $this->breadcrumbs->addItem(
            $this->translator->trans($title, [], $this->translationDomain),
            $this->urlGenerator->generate($route, $routeParams)
        );

        return $this;
    }

    public function hasItems(): bool
    {
        return count($this->breadcrumbs) > 0;
    }

    public function getInstance(): Breadcrumbs
    {
        return $this->breadcrumbs;
    }
}

