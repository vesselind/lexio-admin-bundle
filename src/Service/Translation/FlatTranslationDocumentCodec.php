<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class FlatTranslationDocumentCodec
{
    private const FILE_PATTERN = '/^(?<domain>[a-z0-9]+(?:_[a-z0-9]+)*)\.(?<locale>[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*)\.yaml$/D';
    private const KEY_PATTERN = '/^(?=.*\S)[^\r\n]+$/uD';
    private const UNQUOTED_KEY_PATTERN = '/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*$/D';

    /** @return array<string, string> */
    public function parse(string $content): array
    {
        $lineNumbers = $this->assertFlatDocument($content);

        try {
            $parsed = Yaml::parse($content);
        } catch (ParseException $exception) {
            $line = $exception->getParsedLine();
            throw new InvalidTranslationDocumentException(
                $this->formatYamlParseMessage($exception),
                previous: $exception,
                documentLine: $line > 0 ? $line : null,
            );
        }

        if (null === $parsed || [] === $parsed) {
            return [];
        }

        if (!is_array($parsed)) {
            throw new InvalidTranslationDocumentException(
                'The translation document must contain a flat mapping.',
                documentLine: $this->firstDocumentLine($content),
            );
        }

        $entries = [];
        foreach ($parsed as $key => $value) {
            $line = is_string($key) ? ($lineNumbers[$key] ?? null) : $this->firstDocumentLine($content);
            if (!is_string($key) || !is_string($value)) {
                throw new InvalidTranslationDocumentException(
                    'Translation keys and values must be strings.',
                    documentLine: $line,
                );
            }

            $this->assertKey($key, $line);
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
            $content .= $this->serializeKey($key) . ': ' . $this->serializeValue($value) . "\n";
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

        return ['domain' => $matches['domain'], 'locale' => $matches['locale']];
    }

    public function assertKey(string $key, ?int $line = null): void
    {
        $this->assertKeyAtLine($key, $line);
    }

    private function assertKeyAtLine(string $key, ?int $line): void
    {
        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw new InvalidTranslationDocumentException('The translation key is invalid.', documentLine: $line);
        }
    }

    /** @return array<string, int> */
    private function assertFlatDocument(string $content): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $content);
        if (false === $lines) {
            throw new InvalidTranslationDocumentException('The translation document cannot be inspected.');
        }

        $keys = [];
        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#')) {
                continue;
            }

            if ('---' === $trimmed || '...' === $trimmed || 1 === preg_match('/^\s/', $line)) {
                throw new InvalidTranslationDocumentException(
                    'Only one flat translation record per line is supported.',
                    documentLine: $lineNumber,
                );
            }

            $separator = $this->findMappingSeparator($line);
            if (false === $separator) {
                throw new InvalidTranslationDocumentException(
                    'The translation document contains an invalid record.',
                    documentLine: $lineNumber,
                );
            }

            $key = $this->parseKey(trim(substr($line, 0, $separator)), $lineNumber);
            if (isset($keys[$key])) {
                throw new InvalidTranslationDocumentException(
                    'The translation document contains duplicate keys.',
                    documentLine: $lineNumber,
                );
            }

            $keys[$key] = $lineNumber;
        }

        return $keys;
    }

    private function parseKey(string $rawKey, int $line): string
    {
        if ('' === $rawKey) {
            throw new InvalidTranslationDocumentException('The translation key is invalid.', documentLine: $line);
        }

        try {
            $key = Yaml::parse($rawKey);
        } catch (ParseException $exception) {
            throw new InvalidTranslationDocumentException(
                'The translation key is invalid.',
                previous: $exception,
                documentLine: $line,
            );
        }

        if (!is_string($key)) {
            throw new InvalidTranslationDocumentException('The translation key is invalid.', documentLine: $line);
        }

        $this->assertKeyAtLine($key, $line);

        return $key;
    }

    private function serializeKey(string $key): string
    {
        if (1 === preg_match(self::UNQUOTED_KEY_PATTERN, $key)) {
            return $key;
        }

        return "'" . str_replace("'", "''", $key) . "'";
    }

    private function findMappingSeparator(string $line): int|false
    {
        $quote = null;
        $escaped = false;
        $length = strlen($line);

        for ($index = 0; $index < $length; ++$index) {
            $character = $line[$index];
            if (null === $quote) {
                if (0 === $index && ("'" === $character || '"' === $character)) {
                    $quote = $character;
                    continue;
                }

                if (':' === $character) {
                    return $index;
                }

                continue;
            }

            if ('"' === $quote) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ('\\' === $character) {
                    $escaped = true;
                    continue;
                }

                if ('"' === $character) {
                    $quote = null;
                }

                continue;
            }

            if ("'" === $character) {
                if ($index + 1 < $length && "'" === $line[$index + 1]) {
                    ++$index;
                    continue;
                }

                $quote = null;
            }
        }

        return false;
    }

    private function firstDocumentLine(string $content): ?int
    {
        $lines = preg_split('/\r\n|\n|\r/', $content);
        if (false === $lines) {
            return null;
        }

        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ('' !== $trimmed && !str_starts_with($trimmed, '#')) {
                return $index + 1;
            }
        }

        return null;
    }

    private function formatYamlParseMessage(ParseException $exception): string
    {
        $message = $exception->getMessage();
        $line = $exception->getParsedLine();
        if ($line > 0) {
            $location = ' at line ' . $line;
            $position = strpos($message, $location);
            if (false !== $position) {
                $message = substr($message, 0, $position);
            }
        }

        $message = rtrim($message, '.');
        $snippet = trim($exception->getSnippet());
        if ('' !== $snippet) {
            $message .= sprintf(' (near "%s")', $snippet);
        }

        return 'The translation document contains invalid YAML: ' . $message . '.';
    }
}
