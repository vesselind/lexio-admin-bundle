<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\EventSubscriber;

use Lexio\AdminBundle\Api\ApiRequestMatcher;
use Lexio\AdminBundle\EventSubscriber\ApiLocaleSubscriber;
use Lexio\AdminBundle\Exception\ApiValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\LocaleListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Validation;

final class ApiLocaleSubscriberTest extends TestCase
{
    #[DataProvider('localeSelections')]
    public function test_selects_locale_before_native_locale_handling(string $uri, ?string $body, string $expected): void
    {
        $request = Request::create($uri, $body === null ? 'GET' : 'PATCH', server: ['CONTENT_TYPE' => 'application/json'], content: $body);
        $event = $this->requestEvent($request);
        $subscriber = $this->subscriber();

        $subscriber->onKernelRequest($event);
        (new LocaleListener(new RequestStack(), 'bg'))->onKernelRequest($event);
        $subscriber->onKernelControllerArguments($this->argumentsEvent($request));

        self::assertSame($expected, $request->getLocale());
        self::assertSame($expected, $request->attributes->get('_locale'));
    }

    public static function localeSelections(): iterable
    {
        yield 'canonical query' => ['/api/v1/pages/1/show?_locale=en', null, 'en'];
        yield 'legacy query' => ['/api/v1/pages/1/show?locale=en', null, 'en'];
        yield 'legacy JSON' => ['/api/v1/pages/1/update', '{"locale":"en"}', 'en'];
        yield 'canonical precedence' => ['/api/v1/pages/1/update?_locale=en&locale=bg', '{"locale":"bg"}', 'en'];
        yield 'legacy query precedes body' => ['/api/v1/pages/1/update?locale=en', '{"locale":"bg"}', 'en'];
        yield 'default' => ['/api/v1/pages/1/show', null, 'bg'];
        yield 'null legacy locale' => ['/api/v1/pages/1/update', '{"locale":null}', 'bg'];
        yield 'malformed JSON stays with payload mapping' => ['/api/v1/pages/1/update', '{invalid', 'bg'];
    }

    #[DataProvider('invalidLocales')]
    public function test_invalid_locale_is_rejected_only_during_controller_arguments(string $query, string $field): void
    {
        $request = Request::create('/api/v1/pages/1/show?' . $query);
        $subscriber = $this->subscriber();
        $subscriber->onKernelRequest($this->requestEvent($request));
        self::assertSame('bg', $request->attributes->get('_locale'));

        try {
            $subscriber->onKernelControllerArguments($this->argumentsEvent($request));
            self::fail('Expected a locale validation error.');
        } catch (ApiValidationException $exception) {
            self::assertContains($field, array_column($exception->getErrors(), 'field'));
        }
    }

    public static function invalidLocales(): iterable
    {
        yield 'unsupported' => ['_locale=fr', '_locale'];
        yield 'empty' => ['_locale=', '_locale'];
        yield 'array' => ['_locale[]=en', '_locale'];
        yield 'legacy' => ['locale=fr', 'locale'];
    }

    public function test_does_not_change_browser_routes(): void
    {
        $request = Request::create('/en/blog?_locale=bg');
        $request->attributes->set('_locale', 'en');
        $subscriber = $this->subscriber();
        $subscriber->onKernelRequest($this->requestEvent($request));
        $subscriber->onKernelControllerArguments($this->argumentsEvent($request));

        self::assertSame('en', $request->attributes->get('_locale'));
    }

    private function subscriber(): ApiLocaleSubscriber
    {
        return new ApiLocaleSubscriber(new ApiRequestMatcher(), Validation::createValidator(), 'bg', ['bg', 'en']);
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function argumentsEvent(Request $request): ControllerArgumentsEvent
    {
        return new ControllerArgumentsEvent($this->createStub(HttpKernelInterface::class), static fn () => null, [], $request, HttpKernelInterface::MAIN_REQUEST);
    }
}
