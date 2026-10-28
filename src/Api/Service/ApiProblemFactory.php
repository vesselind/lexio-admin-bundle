<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Builds RFC 7807 Problem Details responses for the versioned API.
 *
 * API payloads are intentionally not translated: they are machine-readable
 * contracts consumed by integrators, so all messages are plain English.
 */
final readonly class ApiProblemFactory
{
    /**
     * Default detail message per HTTP status code.
     *
     * @var array<int, string>
     */
    private const array DETAILS = [
        Response::HTTP_BAD_REQUEST => 'The request contains malformed JSON.',
        Response::HTTP_UNAUTHORIZED => 'A valid Bearer API key is required.',
        Response::HTTP_FORBIDDEN => 'Your account does not have permission for this operation.',
        Response::HTTP_NOT_FOUND => 'The requested resource was not found.',
        Response::HTTP_METHOD_NOT_ALLOWED => 'This HTTP method is not supported for this resource.',
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'Send the request body as application/json.',
        Response::HTTP_UNPROCESSABLE_ENTITY => 'The request contains invalid fields or values.',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'The request could not be completed.',
    ];

    /**
     * @param array<string, mixed>  $extra
     * @param array<string, string> $headers
     */
    public function response(int $status, ?string $detail = null, array $extra = [], array $headers = []): JsonResponse
    {
        $payload = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail ?? self::DETAILS[$status] ?? 'The request could not be completed.',
        ];

        return new JsonResponse(array_merge($payload, $extra), $status, array_merge([
            'Content-Type' => 'application/problem+json',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers));
    }
}
