<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\EventSubscriber;

use Lexio\AdminBundle\Api\Service\ApiProblemFactory;
use Lexio\AdminBundle\EventSubscriber\ApiExceptionSubscriber;
use Lexio\AdminBundle\Exception\ApiValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriberTest extends TestCase
{
    private function event(\Throwable $throwable, string $path = '/api/v1/blogs/999999'): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }

    public function test_debug_payload_is_included_when_debug_enabled(): void
    {
        $subscriber = new ApiExceptionSubscriber(new ApiProblemFactory(), true);
        $event = $this->event(new \RuntimeException('Boom'));

        $subscriber->onKernelException($event);

        $response = $event->getResponse();
        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(\RuntimeException::class, $payload['debug']['exception']);
        self::assertSame('Boom', $payload['debug']['message']);
        self::assertIsString($payload['debug']['file']);
        self::assertIsInt($payload['debug']['line']);
        self::assertIsArray($payload['debug']['trace']);
        self::assertNotEmpty($payload['debug']['trace']);
        self::assertArrayHasKey('call', $payload['debug']['trace'][0]);
    }

    public function test_debug_payload_includes_previous_exceptions(): void
    {
        $subscriber = new ApiExceptionSubscriber(new ApiProblemFactory(), true);
        $previous = new \InvalidArgumentException('Inner');
        $event = $this->event(new \RuntimeException('Outer', 0, $previous));

        $subscriber->onKernelException($event);

        $payload = json_decode((string) $event->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['debug']['previous']);
        self::assertSame(\InvalidArgumentException::class, $payload['debug']['previous'][0]['exception']);
        self::assertSame('Inner', $payload['debug']['previous'][0]['message']);
    }

    public function test_debug_payload_is_absent_when_debug_disabled(): void
    {
        $subscriber = new ApiExceptionSubscriber(new ApiProblemFactory(), false);
        $event = $this->event(new \RuntimeException('Boom'));

        $subscriber->onKernelException($event);

        $payload = json_decode((string) $event->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(500, $payload['status']);
        self::assertArrayNotHasKey('debug', $payload);
    }

    public function test_non_api_requests_are_left_to_the_default_handler(): void
    {
        $subscriber = new ApiExceptionSubscriber(new ApiProblemFactory(), true);
        $event = $this->event(new \RuntimeException('Boom'), '/admin/blogs');

        $subscriber->onKernelException($event);

        self::assertNull($event->getResponse());
    }

    public function test_native_and_explicit_validation_preserve_field_errors(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('The title is required.', null, [], null, 'title', null),
            new ConstraintViolation('Use an existing category.', null, [], null, 'primary_category_id', null),
        ]);
        $subscriber = new ApiExceptionSubscriber(new ApiProblemFactory());

        foreach ([
            ApiValidationException::fromViolations($violations),
            new UnprocessableEntityHttpException(previous: new ValidationFailedException(null, $violations)),
        ] as $exception) {
            $event = $this->event($exception);
            $subscriber->onKernelException($event);
            $payload = json_decode((string) $event->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

            self::assertSame(422, $payload['status']);
            self::assertSame([
                ['field' => 'title', 'message' => 'The title is required.'],
                ['field' => 'primary_category_id', 'message' => 'Use an existing category.'],
            ], $payload['errors']);
        }
    }

    public function test_native_validation_errors_and_debug_messages_are_english(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Стойността трябва да е положителна.', 'This value should be positive.', [], null, 'page', 0),
        ]);
        $event = $this->event(new UnprocessableEntityHttpException(
            'Стойността трябва да е положителна.',
            new ValidationFailedException(null, $violations),
        ));

        (new ApiExceptionSubscriber(new ApiProblemFactory(), true))->onKernelException($event);

        $payload = json_decode((string) $event->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([['field' => 'page', 'message' => 'This value should be positive.']], $payload['errors']);
        self::assertStringContainsString('This value should be positive.', $payload['debug']['message']);
        self::assertStringContainsString('This value should be positive.', $payload['debug']['previous'][0]['message']);
        self::assertDoesNotMatchRegularExpression('/\p{Cyrillic}/u', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        self::assertSame(UnprocessableEntityHttpException::class, $payload['debug']['exception']);
        self::assertSame(ValidationFailedException::class, $payload['debug']['previous'][0]['exception']);
    }
}
