<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Utils;

/**
 * Fluent string-cleaning utility.
 * Mostly used for stripping / reformatting user-entered HTML / text.
 */
final class TextPurifier implements \Stringable
{
    public function __construct(public string $content)
    {
    }

    /** Strips tags while keeping only <p>, then replaces them with newlines. */
    public function convertParagraphTagsToNewLines(): static
    {
        $this->content = strip_tags($this->content, '<p>');
        $this->content = str_replace(['</p>', '<p>'], ["\n", ''], $this->content);
        $this->content = trim($this->content);

        return $this;
    }

    /** Converts newlines to <p> tags. */
    public function createParagraphs(): static
    {
        $newContent = '';

        foreach (explode("\n", $this->content) as $line) {
            if (empty($line)) {
                continue;
            }

            if (mb_strlen($line) < 4) {
                $newContent .= '<p>' . trim($line) . ' <same-line>';
            } else {
                $newContent .= '<p>' . trim($line) . '</p>';
            }
        }

        $this->content = str_replace('<same-line><p>', '', $newContent);

        return $this;
    }

    public function stripTags(?string $allowedTags = null): static
    {
        $this->content = strip_tags($this->content, $allowedTags);

        return $this;
    }

    /** Removes multiple consecutive spaces and trims. */
    public function cleanSpaces(): static
    {
        $this->content = str_replace("\u{A0}", ' ', $this->content);
        $this->content = (string) preg_replace('/[ \t]{2,}/u', ' ', $this->content);
        $this->content = trim($this->content);

        return $this;
    }

    /** Reduces multiple consecutive newlines to one. */
    public function cleanNewLines(): static
    {
        $this->content = (string) preg_replace('/[\r\n]{2,}/u', "\n", $this->content);
        $this->content = trim($this->content);

        return $this;
    }

    /** Strips all tags and collapses whitespace. */
    public function plain(): static
    {
        $this->content = trim((string) preg_replace('/\s+/', ' ', strip_tags($this->content)));

        return $this;
    }

    public function truncateWords(int $wordsLimit = 40): static
    {
        if (empty($this->content) || mb_strlen($this->content) <= $wordsLimit) {
            return $this;
        }

        $hasParagraphs = str_contains($this->content, '<p>');

        if ($hasParagraphs) {
            $this->convertParagraphTagsToNewLines();
        }

        $words     = explode(' ', $this->content);
        $truncated = implode(' ', array_slice($words, 0, $wordsLimit));

        if (mb_strlen($truncated) < mb_strlen($this->content)) {
            $truncated .= "...\n";
        }

        $this->content = $truncated;

        if ($hasParagraphs) {
            $this->createParagraphs();
        }

        return $this;
    }

    /** Wraps text into an array of lines no longer than $maxLineLength characters. */
    public function wrapText(int $maxLineLength = 60): array
    {
        $words       = explode(' ', $this->content);
        $lines       = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (strlen($currentLine . ' ' . $word) <= $maxLineLength) {
                $currentLine .= ($currentLine === '' ? '' : ' ') . $word;
            } else {
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    public static function arrayToParagraphs(array $lines): static
    {
        $content = '';

        foreach ($lines as $line) {
            if (!empty($line)) {
                $content .= '<p>' . $line . '</p>';
            }
        }

        return new static($content);
    }

    public function toString(): string
    {
        return $this->content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}

