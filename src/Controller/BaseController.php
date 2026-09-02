<?php

namespace Lexio\AdminBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lexio\AdminBundle\AdminCore\Breadcrumbs\FrontBreadcrumbs;
use Lexio\AdminBundle\Service\EntityFilterer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

abstract class BaseController extends AbstractController
{

    public function manager(): EntityManagerInterface
    {
        return $this->container->get('doctrine')->getManager();
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

    public function breadcrumbs(): FrontBreadcrumbs
    {
        return $this->container->get('front_breadcrumbs');
    }

    public static function getSubscribedServices(): array
    {
        return [
            'twig' => '?' . Environment::class,
            'router' => '?' . RouterInterface::class,
            'doctrine' => '?' . ManagerRegistry::class,
            'form.factory' => '?' . FormFactoryInterface::class,
            'security.token_storage' => '?' . TokenStorageInterface::class,
            'security.csrf.token_manager' => '?' . CsrfTokenManagerInterface::class,
            'parameter_bag' => '?' . ContainerBagInterface::class,
            'translator' => '?' . TranslatorInterface::class,
            'entity_filter_service' => '?' . EntityFilterer::class,
            'front_breadcrumbs' => '?' . FrontBreadcrumbs::class,
            'request_stack' => '?Symfony\Component\HttpFoundation\RequestStack',

        ];
    }
}
