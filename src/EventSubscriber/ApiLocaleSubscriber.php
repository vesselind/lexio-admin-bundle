<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventSubscriber;

use Lexio\AdminBundle\Api\ApiRequestMatcher;
use Lexio\AdminBundle\Exception\ApiValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** Selects the content locale before Symfony loads controller entity arguments. */
final readonly class ApiLocaleSubscriber implements EventSubscriberInterface
{
    private const string INPUT_ATTRIBUTE = '_lexio_api_locale_input';

    /** @param list<string> $locales */
    public function __construct(
        private ApiRequestMatcher $requestMatcher,
        private ValidatorInterface $validator,
        private string $defaultLocale,
        private array $locales,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // After routing (32), before Symfony's locale listeners (16 and 15).
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            // Preserve authorization before reporting invalid locale input.
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -10001],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->requestMatcher->matches($request)) {
            return;
        }

        $request->attributes->set('_locale', $this->defaultLocale);
        $query = $request->query->all();
        $field = '_locale';
        $value = null;

        if (array_key_exists('_locale', $query)) {
            $value = $query['_locale'];
        } elseif (array_key_exists('locale', $query)) {
            $field = 'locale';
            $value = $query['locale'];
        } elseif ($request->getContentTypeFormat() === 'json') {
            try {
                $value = $request->getPayload()->all()['locale'] ?? null;
                $field = 'locale';
            } catch (JsonException) {
                // Leave malformed JSON to the native payload resolver after security.
                return;
            }
        }

        if ($value === null) {
            return;
        }

        $request->attributes->set(self::INPUT_ATTRIBUTE, ['field' => $field, 'value' => $value]);
        if (is_string($value) && in_array($value, $this->locales, true)) {
            $request->attributes->set('_locale', $value);
        }
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        /** @var array{field: string, value: mixed}|null $input */
        $input = $event->getRequest()->attributes->get(self::INPUT_ATTRIBUTE);
        if ($input === null) {
            return;
        }

        $violations = $this->validator->validate($input['value'], [new Type('string'), new Choice(choices: $this->locales)]);
        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations, $input['field']);
        }
    }
}
