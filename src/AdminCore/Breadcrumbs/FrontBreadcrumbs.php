<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Breadcrumbs;

use Huluti\BreadcrumbsBundle\Model\Breadcrumbs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

