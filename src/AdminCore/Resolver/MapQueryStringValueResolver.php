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
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the core RequestPayloadValueResolver to:
 *  - automatically hydrate entity-typed properties from their ID query parameter, and
 *  - support the existing MapQueryString mapping pipeline.
 */
class MapQueryStringValueResolver extends \Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver
{
    private const array CONTEXT_DENORMALIZE = [
        'collect_denormalization_errors' => true,
    ];

    public function __construct(
        private readonly SerializerInterface&DenormalizerInterface $serializer,
        private readonly ?ValidatorInterface                       $validator = null,
        private EntityManagerInterface                             $entityManager,
        private readonly ?TranslatorInterface                      $translator = null,
        private readonly string                                    $translationDomain = 'validators',
    ) {
        parent::__construct($serializer);
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
                        $this->validator->validate($payload, $constraints, $argument->validationGroups ?? null)
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

        $denormalized = $this->serializer->denormalize(
            $data,
            $argument->getType(),
            'csv',
            $attribute->serializationContext + self::CONTEXT_DENORMALIZE + ['filter_bool' => true]
        );

        $reflector = new \ReflectionClass($argument->getType());

        foreach ($reflector->getProperties() as $property) {
            if (!$property->hasType()) {
                continue;
            }

            $type = $property->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $fqcn = $type->getName();

            if (!$this->isEntity($fqcn)) {
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

    private function isEntity(string $class): bool
    {
        try {
            return null !== $this->entityManager->getClassMetadata($class)->getIdentifier();
        } catch (\Doctrine\Persistence\Mapping\MappingException) {
            return false;
        }
    }
}

