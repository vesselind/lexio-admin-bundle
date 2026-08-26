<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Twig;

use Lexio\AdminBundle\Utils\TextPurifier;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\UnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * General-purpose Twig extension: date formatting, text utilities, settings, URL helpers.
 */
final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SerializerInterface   $serializer,
        private readonly Packages              $packages,
        private readonly string                $siteBaseUrl,
        /** @var list<string> */
        private readonly array                 $locales,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('stimulus_modal', $this->stimulusModal(...), ['is_safe' => ['html']]),
            new TwigFunction('get_locales', $this->getLocales(...)),
            new TwigFunction('absolute_path', $this->absolutePath(...)),
            new TwigFunction('absolute_asset', $this->absoluteAsset(...)),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatted_date', [$this, 'formattedDate']),
            new TwigFilter('formatted_datetime', [$this, 'formattedDateTime']),
            new TwigFilter('strip_words', [$this, 'stripWords'], ['is_safe' => ['html']]),
            new TwigFilter('highlight', [$this, 'highlight'], ['is_safe' => ['html']]),
            new TwigFilter('list_style', [$this, 'listStyle'], ['is_safe' => ['html']]),
            new TwigFilter('add_links', [$this, 'addLinks'], ['is_safe' => ['html']]),
            new TwigFilter('cast_to_array', [$this, 'castToArray']),
            new TwigFilter('camel_to_title', [$this, 'camelToTitle']),
            new TwigFilter('anonymize', [$this, 'anonymize']),
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
        ];
    }

    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    public function formattedDate(?\DateTimeInterface $value): string
    {
        if (!$value) {
            return '';
        }

        return $value->format('d.m.Y');
    }

    public function formattedDateTime(?\DateTimeInterface $value): string
    {
        if (!$value) {
            return '';
        }

        return $value->format('d.m.Y H:i');
    }

    public function stripWords(mixed $string, int $wordsLimit = 40): string|Markup
    {
        if (!$string) {
            return '';
        }

        if (mb_strlen((string) $string) <= $wordsLimit) {
            return (string) $string;
        }

        $containsParagraphs = str_contains((string) $string, '<p>');

        if ($containsParagraphs) {
            $string = (new TextPurifier((string) $string))->convertParagraphTagsToNewLines()->toString();
        }

        $words     = explode(' ', (string) $string);
        $truncated = implode(' ', array_slice($words, 0, $wordsLimit));

        if (mb_strlen($truncated) < mb_strlen((string) $string)) {
            $truncated .= "...\n";
        }

        if ($containsParagraphs) {
            $truncated = (new TextPurifier($truncated))->createParagraphs()->toString();
        }

        return new Markup($truncated, 'utf-8');
    }

    public function highlight(string $item, ?string $words = null): Markup
    {
        if ($words === null || $words === '') {
            return new Markup($item, 'utf-8');
        }

        foreach (explode(' ', $words) as $word) {
            $item = (string) preg_replace('/(' . preg_quote($word, '/') . ')/ius', '<em>$1</em>', $item);
        }

        return new Markup($item, 'utf-8');
    }

    public function listStyle(string $value): Markup
    {
        $value = (string) preg_replace('/<ul>/ius', '<ul class="list-style-one">', $value);

        return new Markup($value, 'utf-8');
    }

    /**
     * Converts plain URLs in text to clickable HTML anchor tags.
     */
    public function addLinks(string $text): Markup
    {
        $linked = (string) preg_replace(
            '/(https?:\/\/[^\s<>"\']+)/i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );

        return new Markup($linked, 'utf-8');
    }

    /** @return array<mixed> */
    public function castToArray(mixed $object): array
    {
        /** @phpstan-ignore-next-line */
        return $this->serializer->normalize($object, 'array');
    }

    public function camelToTitle(string $label): string
    {
        $snake = (new UnicodeString($label))->snake();
        $parts = explode('_', $snake->toString());

        return (new UnicodeString(implode(' ', $parts)))->title()->toString();
    }

    /**
     * Anonymizes an email address or plain string by masking the middle portion.
     *
     * Examples:
     *   john.doe@example.com → j*******@example.com
     *   JohnDoe              → J*****e
     */
    public function anonymize(string $value): string
    {
        if (str_contains($value, '@')) {
            [$local, $domain] = explode('@', $value, 2);
            $masked = substr($local, 0, 1) . str_repeat('*', max(1, mb_strlen($local) - 1));

            return $masked . '@' . $domain;
        }

        $len = mb_strlen($value);

        if ($len <= 2) {
            return $value;
        }

        return mb_substr($value, 0, 1)
            . str_repeat('*', $len - 2)
            . mb_substr($value, -1);
    }

    public function formatBytes(?int $bytes, int $precision = 1): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exp   = (int) floor(log($bytes, 1024));
        $exp   = min($exp, count($units) - 1);

        return round($bytes / (1024 ** $exp), $precision) . ' ' . $units[$exp];
    }

    // -------------------------------------------------------------------------
    // Functions
    // -------------------------------------------------------------------------

    /**
     * Generates stimulus `data-*` attributes for a modal controller listener.
     *
     * Usage: <div {{ stimulus_modal('my-modal') }}>
     */
    public function stimulusModal(string $modalId): Markup
    {
        $safeId = htmlspecialchars($modalId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attrs  = 'id="' . $safeId . '" data-controller="modal" '
            . 'data-action="modal:open:' . $safeId . '@window->modal#openModal"';

        return new Markup($attrs, 'UTF-8');
    }

    /** @return list<string> */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * Generates an absolute URL for a route (safe in async/email contexts).
     *
     * @param array<string, mixed> $parameters
     */
    public function absolutePath(string $route, array $parameters = []): string
    {
        $relativePath = $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::RELATIVE_PATH);

        return rtrim($this->siteBaseUrl, '/') . '/' . ltrim($relativePath, '/');
    }

    /**
     * Generates an absolute URL for an asset (safe in async/email contexts).
     */
    public function absoluteAsset(string $path): string
    {
        $assetPath = ltrim($this->packages->getUrl($path), '/');

        return rtrim($this->siteBaseUrl, '/') . '/' . $assetPath;
    }
}




