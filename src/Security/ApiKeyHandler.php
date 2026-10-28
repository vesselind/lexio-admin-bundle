<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Security;

use Lexio\AdminBundle\Api\Service\ApiKeyGenerator;
use Lexio\AdminBundle\Contract\Api\ApiKeyUserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Resolves the raw Bearer secret to a host user by its stored hash.
 */
final readonly class ApiKeyHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiKeyUserProviderInterface $userRepository,
        private ApiKeyGenerator $apiKeyGenerator,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $user = $this->userRepository->findByHash($this->apiKeyGenerator->hash($accessToken));

        if ($user === null) {
            throw new BadCredentialsException('Invalid API key.');
        }

        return new UserBadge($user->getUserIdentifier(), static fn (): UserInterface => $user);
    }
}
