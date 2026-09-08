<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\DependencyInjection\Compiler;

use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Resolves the host application's concrete image entity for bundle mappings.
 */
final class ResolveImageEntityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $parameter = 'lexio_admin.image_entity_class';

        if (!$container->hasParameter($parameter)) {
            return;
        }

        $imageEntityClass = $container->getParameter($parameter);

        if (!is_string($imageEntityClass) || $imageEntityClass === '') {
            return;
        }

        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            return;
        }

        $container
            ->findDefinition('doctrine.orm.listeners.resolve_target_entity')
            ->addMethodCall('addResolveTargetEntity', [
                ImageEntityInterface::class,
                $imageEntityClass,
                [],
            ]);
    }
}
