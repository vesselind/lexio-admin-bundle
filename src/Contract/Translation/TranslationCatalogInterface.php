<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Translation;

interface TranslationCatalogInterface
{
    public function isEnabled(): bool;

    /** @return iterable<int, TranslationFile> */
    public function listFiles(): iterable;

    /** @return iterable<int, TranslationEntry> */
    public function getEntries(string $domain, string $locale): iterable;

    /**
     * @throws TranslationCatalogException when the file, key, or value cannot be updated safely
     */
    public function update(string $domain, string $locale, string $key, string $value): void;
}
