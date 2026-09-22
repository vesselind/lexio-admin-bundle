<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures;

use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
use Lexio\AdminBundle\EventListener\PageAttributeListener;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
    }

    protected function configureContainer(ContainerBuilder $container): void
    {
        $container->loadFromExtension('framework', [
            'test' => true,
            'secret' => 'test-secret',
            'default_locale' => 'bg',
        ]);
        $container->loadFromExtension('twig', [
            'strict_variables' => true,
        ]);

        $container
            ->register(LocaleAwarePageManager::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setPublic(true);
        $container->setAlias(PageManagerInterface::class, LocaleAwarePageManager::class);

        $container
            ->register(PageAttributeListener::class)
            ->setAutowired(true)
            ->setAutoconfigured(true)
            ->setPublic(true);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
