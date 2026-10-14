<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\User;

/**
 * Creates the account and sends the host application's password setup email.
 */
interface UserInvitationInterface
{
    /**
     * Hash an initial password, persist the user and invitation, and send the email.
     * Returns the invitation expiry used by the admin success message.
     */
    public function invite(UserEntityInterface $user): \DateTimeImmutable;
}
