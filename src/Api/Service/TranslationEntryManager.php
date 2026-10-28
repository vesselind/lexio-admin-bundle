<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Lexio\AdminBundle\Api\Output\TranslationEntryOutput;
use Lexio\AdminBundle\Api\Query\TranslationEntryListQuery;
use Lexio\AdminBundle\Exception\ApiValidationException;
use Lexio\AdminBundle\Contract\Translation\TranslationCatalogInterface;
use Lexio\AdminBundle\Service\Translation\TranslationEntryFilter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class TranslationEntryManager
{
    /** @param list<string> $locales */
    public function __construct(
        private TranslationCatalogInterface $catalog,
        private TranslationEntryFilter $entryFilter,
        private ValidatorInterface $validator,
        private string $defaultLocale,
        private array $locales,
    ) {
    }

    /** @return list<TranslationEntryOutput> */
    public function list(TranslationEntryListQuery $query): array
    {
        $this->assertEnabled();
        $locale = $this->resolveLocale($query->getLocale());
        $sort = $query->getSort() ?? 'domain';
        $this->validateChoice('sort', $sort, TranslationEntryListQuery::sortableFields());
        $search = (new UnicodeString(trim($query->getSearchPhrase())))->lower()->toString();
        $items = [];

        foreach ($this->catalog->listFiles() as $file) {
            if ($file->locale !== $locale || ($query->domain !== null && $file->domain !== $query->domain)) {
                continue;
            }

            $entries = $this->entryFilter->filter(
                $this->catalog->getEntries($file->domain, $locale),
                $query->key ?? '',
                $query->value ?? '',
            );
            $domainMatchesSearch = $search !== '' && $this->containsInsensitive($file->domain, $search);

            foreach ($entries as $entry) {
                if ($search !== ''
                    && !$domainMatchesSearch
                    && !$this->containsInsensitive($entry->key, $search)
                    && !$this->containsInsensitive($entry->value, $search)
                ) {
                    continue;
                }

                $items[] = new TranslationEntryOutput($file->domain, $locale, $entry->key, $entry->value);
            }
        }

        $direction = strtolower($query->getOrder() ?? 'asc') === 'desc' ? -1 : 1;
        usort($items, static fn (TranslationEntryOutput $left, TranslationEntryOutput $right): int =>
            $direction * ([$left->$sort, $left->domain, $left->key] <=> [$right->$sort, $right->domain, $right->key])
        );

        return $items;
    }

    public function show(string $domain, string $key, ?string $locale = null): TranslationEntryOutput
    {
        $this->assertEnabled();
        $locale = $this->resolveLocale($locale);

        foreach ($this->catalog->listFiles() as $file) {
            if ($file->domain !== $domain || $file->locale !== $locale) {
                continue;
            }

            foreach ($this->catalog->getEntries($domain, $locale) as $entry) {
                if ($entry->key === $key) {
                    return new TranslationEntryOutput($domain, $locale, $entry->key, $entry->value);
                }
            }

            break;
        }

        throw new NotFoundHttpException('The translation entry does not exist.');
    }

    public function update(string $domain, string $key, string $value, ?string $locale = null): TranslationEntryOutput
    {
        $entry = $this->show($domain, $key, $locale);
        $this->catalog->update($entry->domain, $entry->locale, $entry->key, $value);

        return new TranslationEntryOutput($entry->domain, $entry->locale, $entry->key, $value);
    }

    private function assertEnabled(): void
    {
        if (!$this->catalog->isEnabled()) {
            throw new NotFoundHttpException();
        }
    }

    private function resolveLocale(?string $locale): string
    {
        $locale ??= $this->defaultLocale;
        $this->validateChoice('locale', $locale, $this->locales);

        return $locale;
    }

    /** @param list<string> $choices */
    private function validateChoice(string $field, string $value, array $choices): void
    {
        $violations = $this->validator->validate($value, new Choice(choices: $choices));

        if ($violations->count() > 0) {
            throw ApiValidationException::fromViolations($violations, $field);
        }
    }

    private function containsInsensitive(string $haystack, string $needle): bool
    {
        return (new UnicodeString($haystack))->lower()->indexOf($needle) !== null;
    }
}
