<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Breadcrumbs;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
use Lexio\AdminBundle\Page\BasePage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontBreadcrumbs
{
    public function __construct(
        private readonly RouterInterface     $router,
        private readonly Breadcrumbs         $breadcrumbs,
        private readonly TranslatorInterface $translator,
        #[Autowire('%lexio_admin.front_home_page_route%')]
        private readonly string $frontHomePageRoute,
        private readonly PageManagerInterface $pageManager,
        private readonly RequestStack $requestStack,
    ) {
    }


    public function forHome(): static
    {
        $this->breadcrumbs->addItem(
            $this->translator->trans('home', [], 'breadcrumbs'),
            $this->router->generate($this->frontHomePageRoute)
        );

        return $this;
    }

    /**
     * Adds the current page title as an unlinked, untranslated breadcrumb.
     *
     * Missing pages, unsupported page objects, and null or blank titles are silently skipped.
     *
     * @param class-string<\Lexio\AdminBundle\Page\BasePage> $pageClass
     */
    public function forDynamicPage(string $pageClass, ?string $locale = null): static
    {
        $page = $this->pageManager->getPageObject(
            $pageClass,
            $locale ?? $this->requestStack->getCurrentRequest()?->getLocale(),
        );

        if (!$page instanceof BasePage) {
            return $this;
        }

        $title = $page->getTitle();
        if ($title === null || trim($title) === '') {
            return $this;
        }

        $this->breadcrumbs->addItem($title, '', [], false);

        return $this;
    }

    /**
     * The translation domain is by default the `breadcrumbs`.
     * @param string $text #TranslationKey
     * @param string $route #Route
     * @param array<string, mixed> $routeParams #RouteParams
     * @return $this
     */
    public function addItem(string $text, string $route, array $routeParams = []): static
    {
        $this->breadcrumbs->addItem(
            $this->translator->trans($text, [], 'breadcrumbs'),
            $this->router->generate($route, $routeParams)
        );

        return $this;
    }
}

