<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\DependencyInjection\Compiler;

use Lexio\AdminBundle\Contract\Notification\NotificationUserInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the user entity class as the target for NotificationUserInterface
 * via Doctrine's ResolveTargetEntityListener.
 *
 * This runs after all extensions are loaded, so the Doctrine service
 * is guaranteed to exist.
 */
final class ResolveNotificationUserPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $userEntityClass = $container->getParameter('lexio_admin.user_entity_class');

        if (!\is_string($userEntityClass) || $userEntityClass === '') {
            return;
        }

        if (!$container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
            return;
        }

        $container
            ->findDefinition('doctrine.orm.listeners.resolve_target_entity')
            ->addMethodCall('addResolveTargetEntity', [
                NotificationUserInterface::class,
                $userEntityClass,
                [],
            ]);
    }
}

