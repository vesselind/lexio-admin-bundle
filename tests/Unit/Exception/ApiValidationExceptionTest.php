<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Exception;

use Lexio\AdminBundle\Exception\ApiValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class ApiValidationExceptionTest extends TestCase
{
    public function test_english_templates_replace_localized_messages_and_preserve_parameters(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Стойността трябва да е положителна.', 'This value should be positive.', [], null, 'page', 0),
            new ConstraintViolation('Невалиден тип.', 'This value should be of type {{ type }}.', ['{{ type }}' => 'string'], null, 'title', []),
        ]);

        self::assertSame([
            ['field' => 'page', 'message' => 'This value should be positive.'],
            ['field' => 'title', 'message' => 'This value should be of type string.'],
        ], ApiValidationException::fromViolations($violations)->getErrors());
        self::assertSame('Стойността трябва да е положителна.', $violations[0]->getMessage());
    }

    #[DataProvider('pluralLimits')]
    public function test_english_pluralization_and_field_override(int $limit, string $expected): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'Прекалено кратка стойност.',
                'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.',
                ['{{ limit }}' => $limit],
                null,
                '',
                '',
                $limit,
            ),
        ]);

        self::assertSame([['field' => 'title', 'message' => $expected]], ApiValidationException::fromViolations($violations, 'title')->getErrors());
    }

    public static function pluralLimits(): iterable
    {
        yield 'singular' => [1, 'This value is too short. It should have 1 character or more.'];
        yield 'plural' => [2, 'This value is too short. It should have 2 characters or more.'];
    }

    public function test_messages_without_a_template_are_preserved(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('The title is required.', null, [], null, 'title', null),
        ]);

        self::assertSame([['field' => 'title', 'message' => 'The title is required.']], ApiValidationException::errorsFromViolations($violations));
    }
}
