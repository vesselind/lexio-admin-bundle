<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Exception;

use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Signals invalid API input that must be reported as a 422 Problem Details response.
 */
final class ApiValidationException extends \RuntimeException
{
    /**
     * @param list<array{field: string, message: string}> $errors
     */
    public function __construct(private readonly array $errors, string $message = 'The request contains invalid fields or values.')
    {
        parent::__construct($message);
    }

    public static function fromViolations(ConstraintViolationListInterface $violations, ?string $field = null): self
    {
        return new self(self::errorsFromViolations($violations, $field));
    }

    /** @return list<array{field: string, message: string}> */
    public static function errorsFromViolations(ConstraintViolationListInterface $violations, ?string $field = null): array
    {
        $errors = [];
        $formatter = new IdentityTranslator();

        foreach ($violations as $violation) {
            $template = $violation->getMessageTemplate();
            $parameters = $violation->getParameters();

            if (null !== $plural = $violation->getPlural()) {
                $parameters = ['%count%' => $plural] + $parameters;
            }

            $errors[] = [
                'field' => $field ?? $violation->getPropertyPath(),
                // Format the original English template, including plurals, without catalogues.
                'message' => $formatter->trans(
                    $template !== '' ? $template : (string) $violation->getMessage(),
                    $parameters,
                    locale: 'en',
                ),
            ];
        }

        return $errors;
    }

    public static function field(string $field, string $message): self
    {
        return new self([['field' => $field, 'message' => $message]]);
    }

    /**
     * @return list<array{field: string, message: string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
