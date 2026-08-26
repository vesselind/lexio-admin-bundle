<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationEntry;
use Lexio\AdminBundle\Service\Translation\TranslationEntryFilter;
use PHPUnit\Framework\TestCase;

final class TranslationEntryFilterTest extends TestCase
{
    public function test_it_filters_by_key_and_value_case_insensitively(): void
    {
        $entries = [
            new TranslationEntry('admin.title', 'Заглавие на сайта'),
            new TranslationEntry('admin.subtitle', 'Подзаглавие'),
            new TranslationEntry('dashboard.title', 'Заглавие на таблото'),
        ];

        $filtered = new TranslationEntryFilter()->filter($entries, 'ADMIN', 'сайта');

        self::assertCount(1, $filtered);
        self::assertSame('admin.title', $filtered[0]->key);
    }

    public function test_it_returns_all_entries_when_filters_are_empty(): void
    {
        $entries = [new TranslationEntry('admin.title', 'Title')];

        self::assertSame($entries, new TranslationEntryFilter()->filter($entries, '  ', ''));
    }
}
