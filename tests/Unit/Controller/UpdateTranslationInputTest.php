<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Controller;

use Lexio\AdminBundle\Controller\Admin\UpdateTranslationInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class UpdateTranslationInputTest extends TestCase
{
    #[DataProvider('validKeyProvider')]
    public function test_it_accepts_logical_keys_supported_by_the_yaml_catalog(string $key): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        self::assertCount(0, $validator->validate(new UpdateTranslationInput('messages', 'en', $key, 'Updated')));
    }

    /** @return iterable<string, array{string}> */
    public static function validKeyProvider(): iterable
    {
        yield 'dot key' => ['label.title'];
        yield 'sentence' => ['Invalid credentials.'];
        yield 'apostrophe and colon' => ["User's name: invalid"];
        yield 'Unicode and slash' => ['етикет/име'];
    }

    #[DataProvider('invalidKeyProvider')]
    public function test_it_rejects_blank_and_multiline_keys(string $key): void
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $violations = $validator->validate(new UpdateTranslationInput('messages', 'en', $key, 'Updated'));

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('key', $violations[0]->getPropertyPath());
    }

    /** @return iterable<string, array{string}> */
    public static function invalidKeyProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'whitespace only' => ['   '];
        yield 'line feed' => ["label.title\nneighbor"];
        yield 'carriage return' => ["label.title\rneighbor"];
    }
}
