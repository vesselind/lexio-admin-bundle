<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Security;

use Lexio\AdminBundle\Api\Service\ApiProblemFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final readonly class ApiAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private ApiProblemFactory $problems)
    {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return $this->problems->response(Response::HTTP_FORBIDDEN);
    }
}
