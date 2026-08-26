<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Breadcrumbs;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Lexio\AdminBundle\AdminCore\AdminUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminBreadcrumbs
{
    public function __construct(
        private readonly Breadcrumbs         $breadcrumbs,
        private readonly AdminUrlGenerator   $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function setEntityFqcn(string $entityFqcn): static
    {
        $this->adminUrlGenerator->setEntityFqcn($entityFqcn);

        return $this;
    }

    public function forHome(): void
    {
        $this->breadcrumbs->addItem(
            $this->translator->trans('Home', [], 'admin'),
            $this->adminUrlGenerator->getHomeLink()
        );
    }

    public function forIndex(string $indexTitle): void
    {
        $this->forHome();

        $this->breadcrumbs->addItem($indexTitle, $this->adminUrlGenerator->indexLink());
    }

    public function forPage(string $indexTitle, string $pageTitle): void
    {
        $this->forIndex($indexTitle);

        $this->breadcrumbs->addItem($pageTitle);
    }

    public function getInstance(): Breadcrumbs
    {
        return $this->breadcrumbs;
    }
}

