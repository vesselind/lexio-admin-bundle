<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber;

use Lexio\AdminBundle\Api\Service\ApiProblemFactory;
use Lexio\AdminBundle\Api\ApiRequestMatcher;
use Lexio\AdminBundle\Exception\ApiValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Converts every exception raised on /api/v1 into RFC 7807 JSON,
 * regardless of the Accept header. In debug mode the response also carries
 * the exception class, message, source location and stack trace.
 */
final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private const int MAX_PREVIOUS_EXCEPTIONS = 5;

    public function __construct(
        private ApiProblemFactory $problems,
        private bool $debug = false,
        private ApiRequestMatcher $requestMatcher = new ApiRequestMatcher(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after the firewall exception listener (priority 1) so the
            // dedicated entry point / access-denied handler can answer first.
            KernelEvents::EXCEPTION => ['onKernelException', -5],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->requestMatcher->matches($request) || $event->hasResponse()) {
            return;
        }

        $throwable = $event->getThrowable();
        $extra = $this->debug ? ['debug' => $this->debugContext($throwable)] : [];

        if ($throwable instanceof ApiValidationException) {
            $extra['errors'] = $throwable->getErrors();
            $event->setResponse($this->problems->response(422, extra: $extra));

            return;
        }

        if ($throwable instanceof AccessDeniedException) {
            $event->setResponse($this->problems->response(Response::HTTP_FORBIDDEN, extra: $extra));

            return;
        }

        $status = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;
        $headers = $throwable instanceof HttpExceptionInterface ? $throwable->getHeaders() : [];

        $previous = $throwable->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $extra['errors'] = ApiValidationException::errorsFromViolations($previous->getViolations());
        }

        $event->setResponse($this->problems->response($status, extra: $extra, headers: $headers));
    }

    /**
     * Debug-only payload: never present when kernel.debug is disabled.
     *
     * @return array<string, mixed>
     */
    private function debugContext(\Throwable $throwable): array
    {
        $context = $this->exceptionContext($throwable);

        $previous = [];
        $cursor = $throwable->getPrevious();

        while ($cursor !== null && \count($previous) < self::MAX_PREVIOUS_EXCEPTIONS) {
            $previous[] = $this->exceptionContext($cursor);
            $cursor = $cursor->getPrevious();
        }

        if ($previous !== []) {
            $context['previous'] = $previous;
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private function exceptionContext(\Throwable $throwable): array
    {
        $validationFailure = $throwable instanceof ValidationFailedException ? $throwable : $throwable->getPrevious();
        $message = $validationFailure instanceof ValidationFailedException
            ? implode("\n", array_column(ApiValidationException::errorsFromViolations($validationFailure->getViolations()), 'message'))
            : $throwable->getMessage();

        return [
            'exception' => $throwable::class,
            'message' => $message,
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => $this->formatTrace($throwable),
        ];
    }

    /**
     * @return list<array{file: string|null, line: int|null, call: string}>
     */
    private function formatTrace(\Throwable $throwable): array
    {
        $trace = [];

        foreach ($throwable->getTrace() as $frame) {
            $trace[] = [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'call' => sprintf(
                    '%s%s%s()',
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'],
                ),
            ];
        }

        return $trace;
    }
}
