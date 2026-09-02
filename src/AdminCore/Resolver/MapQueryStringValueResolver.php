<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Resolver;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the core RequestPayloadValueResolver to:
 *  - automatically hydrate entity-typed properties from their ID query parameter, and
 *  - support the existing MapQueryString mapping pipeline.
 */
final class MapQueryStringValueResolver
{
    private const array CONTEXT_DENORMALIZE = [
        'collect_denormalization_errors' => true,
    ];

    public function __construct(
        private readonly EntityManagerInterface                             $entityManager,
        private readonly SerializerInterface&DenormalizerInterface $serializer,
        private readonly ?ValidatorInterface                       $validator = null,
        private readonly ?TranslatorInterface                      $translator = null,
        private readonly string                                    $translationDomain = 'validators',
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $arguments = $event->getArguments();

        foreach ($arguments as $i => $argument) {
            if ($argument instanceof MapQueryString) {
                $payloadMapper        = $this->mapQueryString(...);
                $validationFailedCode = $argument->validationFailedStatusCode;
            } else {
                continue;
            }

            $request = $event->getRequest();

            if (!$argument->metadata->getType()) {
                throw new \LogicException(
                    sprintf('Could not resolve "$%s": argument must be typed.', $argument->metadata->getName())
                );
            }

            if ($this->validator) {
                $violations = new ConstraintViolationList();

                try {
                    $payload = $payloadMapper($request, $argument->metadata, $argument);
                } catch (PartialDenormalizationException $e) {
                    $trans = $this->translator
                        ? $this->translator->trans(...)
                        : static fn (string $m, array $p) => strtr($m, $p);

                    foreach ($e->getErrors() as $error) {
                        $parameters = [];
                        $template   = 'This value was of an unexpected type.';

                        if ($expectedTypes = $error->getExpectedTypes()) {
                            $template                = 'This value should be of type {{ type }}.';
                            $parameters['{{ type }}'] = implode('|', $expectedTypes);
                        }

                        if ($error->canUseMessageForUser()) {
                            $parameters['hint'] = $error->getMessage();
                        }

                        $message = $trans($template, $parameters, $this->translationDomain);
                        $violations->add(new ConstraintViolation($message, $template, $parameters, null, $error->getPath(), null));
                    }

                    $payload = $e->getData();
                }

                if (null !== $payload && !\count($violations)) {
                    $constraints = $argument->constraints ?? null;
                    $violations->addAll(
                        $this->validator->validate(
                            $payload,
                            $constraints,
                            $this->resolveValidationGroups($argument->validationGroups, $event),
                        )
                    );
                }

                if (\count($violations)) {
                    throw HttpException::fromStatusCode(
                        $validationFailedCode,
                        implode("\n", array_map(static fn ($e) => $e->getMessage(), iterator_to_array($violations))),
                        new ValidationFailedException($payload, $violations)
                    );
                }
            } else {
                try {
                    $payload = $payloadMapper($request, $argument->metadata, $argument);
                } catch (PartialDenormalizationException $e) {
                    throw HttpException::fromStatusCode(
                        $validationFailedCode,
                        implode("\n", array_map(static fn ($e) => $e->getMessage(), $e->getErrors())),
                        $e
                    );
                }
            }

            if (null === $payload) {
                $payload = match (true) {
                    $argument->metadata->hasDefaultValue() => $argument->metadata->getDefaultValue(),
                    $argument->metadata->isNullable()      => null,
                    default                                => throw HttpException::fromStatusCode($validationFailedCode),
                };
            }

            $arguments[$i] = $payload;
        }

        $event->setArguments($arguments);
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments',
        ];
    }

    protected function mapQueryString(Request $request, ArgumentMetadata $argument, MapQueryString $attribute): ?object
    {
        if (!($data = $request->query->all()) && ($argument->isNullable() || $argument->hasDefaultValue())) {
            return null;
        }

        $type = $argument->getType();
        if ($type === null || !class_exists($type)) {
            throw new \LogicException('MapQueryString requires a concrete class type.');
        }

        $denormalized = $this->serializer->denormalize(
            $data,
            $type,
            'csv',
            $attribute->serializationContext + self::CONTEXT_DENORMALIZE + ['filter_bool' => true]
        );

        if (!is_object($denormalized)) {
            throw new \LogicException('MapQueryString could not denormalize the query string into an object.');
        }

        $reflector = new \ReflectionClass($type);

        foreach ($reflector->getProperties() as $property) {
            if (!$property->hasType()) {
                continue;
            }

            $type = $property->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $fqcn = $type->getName();

            if (!class_exists($fqcn) || !$this->isEntity($fqcn)) {
                continue;
            }

            $accessor      = PropertyAccess::createPropertyAccessor();
            $propertyQueryId = $accessor->getValue($data, '[' . $property->getName() . ']');

            if (null === $propertyQueryId) {
                continue;
            }

            $entity = $this->entityManager->getRepository($fqcn)->find($propertyQueryId);
            $accessor->setValue($denormalized, $property->getName(), $entity);
        }

        return $denormalized;
    }

    /**
     * @param mixed $validationGroups
     * @return string|GroupSequence|array<int, string>|null
     */
    private function resolveValidationGroups(
        mixed $validationGroups,
        ControllerArgumentsEvent $event,
    ): string|GroupSequence|array|null {
        $resolvedGroups = $validationGroups;

        if ($resolvedGroups instanceof \Closure) {
            $resolvedGroups = $event->evaluate($resolvedGroups, null);
        }

        if ($resolvedGroups === null || is_string($resolvedGroups) || $resolvedGroups instanceof GroupSequence) {
            return $resolvedGroups;
        }

        if (!is_array($resolvedGroups)) {
            throw new \LogicException('Validation groups must be a string, an array of strings, or a GroupSequence.');
        }

        $groups = [];
        foreach ($resolvedGroups as $group) {
            if ($group instanceof \Closure || $group instanceof GroupSequence) {
                throw new \LogicException('Nested validation group expressions are not supported.');
            }

            if (!is_string($group)) {
                throw new \LogicException('Validation groups must be strings.');
            }

            $groups[] = $group;
        }

        return $groups;
    }

    private function isEntity(string $class): bool
    {
        try {
            return $this->entityManager->getClassMetadata($class)->getIdentifier() !== [];
        } catch (\Doctrine\Persistence\Mapping\MappingException) {
            return false;
        }
    }
}

