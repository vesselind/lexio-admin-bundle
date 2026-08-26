<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationEntry;
use Symfony\Component\String\UnicodeString;

final readonly class TranslationEntryFilter
{
    /**
     * @param iterable<int, TranslationEntry> $entries
     * @return list<TranslationEntry>
     */
    public function filter(iterable $entries, string $key, string $value): array
    {
        $key = trim($key);
        $value = trim($value);
        $filteredEntries = [];

        foreach ($entries as $entry) {
            if ('' !== $key && !$this->containsInsensitive($entry->key, $key)) {
                continue;
            }

            if ('' !== $value && !$this->containsInsensitive($entry->value, $value)) {
                continue;
            }

            $filteredEntries[] = $entry;
        }

        return $filteredEntries;
    }

    private function containsInsensitive(string $haystack, string $needle): bool
    {
        return null !== (new UnicodeString($haystack))->lower()->indexOf((new UnicodeString($needle))->lower());
    }
}
