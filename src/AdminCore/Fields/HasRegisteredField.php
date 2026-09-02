<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Checks whether the entity being displayed has a registered user account
 * (by matching the entity's email against a configurable user entity class).
 *
 * Configuration (lexio_admin.yaml):
 *   lexio_admin:
 *     user_entity_class: App\Entity\User
 */
class HasRegisteredField extends BaseField
{
    /**
     * @param class-string|null $userEntityClass
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ?string $userEntityClass = null,
    ) {
    }

    public function hasRegistered(): bool
    {
        if ($this->userEntityClass === null) {
            throw new \LogicException(
                'HasRegisteredField requires "lexio_admin.user_entity_class" to be configured in lexio_admin.yaml.'
            );
        }

        if (!class_exists($this->userEntityClass)) {
            throw new \LogicException(
                sprintf('Configured user entity class "%s" does not exist.', $this->userEntityClass)
            );
        }

        $entityInstance = $this->getEntityInstance();
        if ($entityInstance === null || !method_exists($entityInstance, 'getEmail')) {
            throw new \RuntimeException(
                'HasRegisteredField can only be used with entities that expose a getEmail() method.'
            );
        }

        $email = $entityInstance->getEmail();

        return $this->entityManager->getRepository($this->userEntityClass)
            ->findOneBy(['email' => $email]) !== null;
    }

    public function mapped(): bool
    {
        return false;
    }
}

