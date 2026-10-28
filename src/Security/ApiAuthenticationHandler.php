<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Security;

use Lexio\AdminBundle\Api\Service\ApiProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final readonly class ApiAuthenticationHandler implements AuthenticationEntryPointInterface, AuthenticationFailureHandlerInterface
{
    public function __construct(private ApiProblemFactory $problems)
    {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->problems->response(Response::HTTP_UNAUTHORIZED, headers: [
            'WWW-Authenticate' => 'Bearer',
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->start($request, $exception);
    }
}
