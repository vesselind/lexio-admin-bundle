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

        try {
            $email = $this->getEntityInstance()->getEmail();
        } catch (\Throwable) {
            throw new \RuntimeException(
                'HasRegisteredField can only be used with entities that expose a getEmail() method.'
            );
        }

        return $this->entityManager->getRepository($this->userEntityClass)
            ->findOneBy(['email' => $email]) !== null;
    }

    public function mapped(): bool
    {
        return false;
    }
}

