<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\InvalidTranslationDocumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FlatTranslationDocumentCodecTest extends TestCase
{
    public function test_it_round_trips_flat_string_translations_one_record_per_line(): void
    {
        $codec = new FlatTranslationDocumentCodec();
        $entries = [
            'label.title' => 'Title',
            'label.info' => "Text: # value\nSecond line",
        ];

        $yaml = $codec->dump($entries);

        self::assertSame($entries, $codec->parse($yaml));
        self::assertCount(2, array_filter(explode("\n", $yaml)));
    }

    public function test_it_accepts_quoted_translation_keys_containing_spaces(): void
    {
        $entries = (new FlatTranslationDocumentCodec())->parse(<<<'YAML'
'Your email is already confirmed': 'Вашият имейл вече е потвърден.'
'Your email is already confirmed.': 'Вашият имейл вече е потвърден.'
YAML);

        self::assertSame([
            'Your email is already confirmed' => 'Вашият имейл вече е потвърден.',
            'Your email is already confirmed.' => 'Вашият имейл вече е потвърден.',
        ], $entries);
    }

    #[DataProvider('invalidDocumentProvider')]
    public function test_it_rejects_non_flat_or_duplicate_documents(string $yaml): void
    {
        $this->expectException(InvalidTranslationDocumentException::class);

        (new FlatTranslationDocumentCodec())->parse($yaml);
    }

    public function test_it_exposes_the_line_of_a_structural_document_error(): void
    {
        try {
            (new FlatTranslationDocumentCodec())->parse("label.title: First\nlabel.title: Second\n");
            self::fail('An invalid translation document must be rejected.');
        } catch (InvalidTranslationDocumentException $exception) {
            self::assertSame(2, $exception->getDocumentLine());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDocumentProvider(): iterable
    {
        yield 'nested mapping' => ["label:\n  title: Nested\n"];
        yield 'duplicate key' => ["label.title: First\nlabel.title: Second\n"];
        yield 'non-string value' => ["label.count: 3\n"];
    }
}
