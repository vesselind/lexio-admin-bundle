<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\User;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The host application's user entity managed by the admin user controller.
 */
interface UserEntityInterface extends UserInterface, PasswordAuthenticatedUserInterface
{
    public function getId(): int|string|null;

    public function getEmail(): ?string;

    public function getFullName(): string;

    /** @param list<string> $roles */
    public function setRoles(array $roles): static;

    public function setPassword(string $password): static;
}
