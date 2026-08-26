<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class FlatTranslationDocumentCodec
{
    private const FILE_PATTERN = '/^(?<domain>[a-z0-9]+(?:_[a-z0-9]+)*)\.(?<locale>[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*)\.yaml$/D';
    private const KEY_PATTERN = '/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*$/D';

    /** @return array<string, string> */
    public function parse(string $content): array
    {
        $this->assertFlatDocument($content);

        try {
            $parsed = Yaml::parse($content);
        } catch (ParseException $exception) {
            throw new InvalidTranslationDocumentException('The translation document contains invalid YAML.', previous: $exception);
        }

        if (null === $parsed || [] === $parsed) {
            return [];
        }

        if (!is_array($parsed)) {
            throw new InvalidTranslationDocumentException('The translation document must contain a flat mapping.');
        }

        $entries = [];
        foreach ($parsed as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidTranslationDocumentException('Translation keys and values must be strings.');
            }

            $this->assertKey($key);
            $entries[$key] = $value;
        }

        return $entries;
    }

    /** @param array<string, string> $entries */
    public function dump(array $entries): string
    {
        $content = '';
        foreach ($entries as $key => $value) {
            $this->assertKey($key);
            $content .= $key . ': ' . $this->serializeValue($value) . "\n";
        }

        $this->parse($content);

        return $content;
    }

    public function serializeValue(string $value): string
    {
        try {
            $yaml = Yaml::dump(
                $value,
                inline: 0,
                indent: 2,
                flags: Yaml::DUMP_FORCE_DOUBLE_QUOTES_ON_VALUES,
            );
        } catch (\Throwable $exception) {
            throw new InvalidTranslationDocumentException('The translation value could not be serialized.', previous: $exception);
        }

        $serialized = rtrim($yaml, "\r\n");
        if ('' === $serialized) {
            throw new InvalidTranslationDocumentException('The translation value could not be serialized.');
        }

        return $serialized;
    }

    /** @return array{domain: string, locale: string}|null */
    public function parseFilename(string $filename): ?array
    {
        $matches = [];
        if (1 !== preg_match(self::FILE_PATTERN, $filename, $matches)) {
            return null;
        }

        if (!isset($matches['domain'], $matches['locale']) || !is_string($matches['domain']) || !is_string($matches['locale'])) {
            return null;
        }

        return ['domain' => $matches['domain'], 'locale' => $matches['locale']];
    }

    public function assertKey(string $key): void
    {
        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw new InvalidTranslationDocumentException('The translation key is invalid.');
        }
    }

    private function assertFlatDocument(string $content): void
    {
        $lines = preg_split('/\r\n|\n|\r/', $content);
        if (false === $lines) {
            throw new InvalidTranslationDocumentException('The translation document cannot be inspected.');
        }

        $keys = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#')) {
                continue;
            }

            if ('---' === $trimmed || '...' === $trimmed || 1 === preg_match('/^\s/', $line)) {
                throw new InvalidTranslationDocumentException('Only one flat translation record per line is supported.');
            }

            $separator = strpos($line, ':');
            if (false === $separator) {
                throw new InvalidTranslationDocumentException('The translation document contains an invalid record.');
            }

            $key = trim(substr($line, 0, $separator));
            $this->assertKey($key);
            if (isset($keys[$key])) {
                throw new InvalidTranslationDocumentException('The translation document contains duplicate keys.');
            }

            $keys[$key] = true;
        }
    }
}
