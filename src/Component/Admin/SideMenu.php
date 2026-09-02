<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Lexio\AdminBundle\AdminCore\Menu\MenuBuilder;
use Lexio\AdminBundle\Controller\BaseController;
use Lexio\AdminBundle\Controller\BaseCrudController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:SideMenu', template: '@LexioAdmin/components/Admin/SideMenu.html.twig')]
class SideMenu extends BaseController
{
    /**
     * Route name for the logo link (home page).
     * Override in the template block or pass as a prop.
     */
    public ?string $homeRoute = null;

    public function __construct(protected readonly MenuBuilder $menuBuilder, protected readonly ParameterBagInterface $parameterBag)
    {
        $homeRoute = $parameterBag->get('lexio_admin.front_home_page_route');

        if ($homeRoute === null) {
            throw new \LogicException('The "lexio_admin.front_home_page_route" parameter must be set in your configuration.');
        }

        if (!is_string($homeRoute)) {
            throw new \LogicException('The "lexio_admin.front_home_page_route" parameter must be a string. Check your bundle configuration.');
        }

        $this->homeRoute = $homeRoute;
    }

    /**
     * @return ArrayCollection<int, \Lexio\AdminBundle\AdminCore\Menu\MenuItemInterface>
     */
    public function getMenuItems(): ArrayCollection
    {
        return $this->menuBuilder->getMenuItems();
    }
}

