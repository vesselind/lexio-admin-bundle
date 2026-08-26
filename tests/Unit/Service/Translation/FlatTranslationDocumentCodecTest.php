<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\InvalidTranslationDocumentException;
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

    /** @dataProvider invalidDocumentProvider */
    public function test_it_rejects_non_flat_or_duplicate_documents(string $yaml): void
    {
        $this->expectException(InvalidTranslationDocumentException::class);

        (new FlatTranslationDocumentCodec())->parse($yaml);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidDocumentProvider(): iterable
    {
        yield 'nested mapping' => ["label:\n  title: Nested\n"];
        yield 'duplicate key' => ["label.title: First\nlabel.title: Second\n"];
        yield 'non-string value' => ["label.count: 3\n"];
    }
}
