<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver;

use Lexio\AdminBundle\Contract\MediaResolver\MediaEmbed;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Replaces the semantic media embeds stored by CKEditor with responsive iframes.
 *
 * CKEditor stores media as <figure class="media"><oembed url="…"></oembed></figure>,
 * which browsers cannot render. The renderer keeps the figure attributes (including
 * author-defined widths) and swaps the oembed element for a playable iframe.
 */
final readonly class MediaEmbedRenderer
{
    private const FIGURE_PATTERN = '~<figure\b([^>]*)>(.*?)</figure>~is';
    private const OEMBED_PATTERN = '~<oembed\b[^>]*\burl\s*=\s*(["\'])(.*?)\1[^>]*>(?:</oembed>)?~is';
    private const CLASS_PATTERN = '~\bclass\s*=\s*(["\'])(.*?)\1~i';

    public function __construct(
        private MediaResolver $resolver,
        private TranslatorInterface $translator,
    ) {
    }

    public function render(?string $content): string
    {
        if (null === $content || '' === $content) {
            return '';
        }

        return $this->replaceLooseOembeds($this->replaceFigures($content));
    }

    private function replaceFigures(string $content): string
    {
        $result = preg_replace_callback(
            self::FIGURE_PATTERN,
            function (array $matches): string {
                return $this->replaceFigure($matches[0], $matches[1], $matches[2]);
            },
            $content,
        );

        return $result ?? $content;
    }

    private function replaceFigure(string $figure, string $attributes, string $inner): string
    {
        if (!preg_match(self::CLASS_PATTERN, $attributes, $classMatches) || !preg_match('~\bmedia\b~i', $classMatches[2])) {
            return $figure;
        }

        if (!preg_match(self::OEMBED_PATTERN, $inner, $oembedMatches)) {
            return $figure;
        }

        $embed = $this->resolver->resolve($this->decodeUrl($oembedMatches[2]));

        if (null === $embed) {
            return $figure;
        }

        return '<figure' . $attributes . '>' . $this->renderEmbed($embed) . '</figure>';
    }

    private function replaceLooseOembeds(string $content): string
    {
        $result = preg_replace_callback(
            self::OEMBED_PATTERN,
            function (array $matches): string {
                $embed = $this->resolver->resolve($this->decodeUrl($matches[2]));

                if (null === $embed) {
                    return $matches[0];
                }

                return '<figure class="media">' . $this->renderEmbed($embed) . '</figure>';
            },
            $content,
        );

        return $result ?? $content;
    }

    private function renderEmbed(MediaEmbed $embed): string
    {
        $provider = $this->escape($embed->provider);
        $src = $this->escape($embed->src);
        $title = $this->escape($this->translator->trans('media_embed.iframe_title'));

        if (null !== $embed->height) {
            return '<div class="lexio-media-embed lexio-media-embed--' . $provider . '" style="width: 100%;">'
                . '<iframe src="' . $src . '" title="' . $title . '" loading="lazy"'
                . ' style="width: 100%; height: ' . $embed->height . 'px; border: 0;"'
                . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
                . ' allowfullscreen></iframe>'
                . '</div>';
        }

        $aspectRatio = $this->escape($embed->aspectRatio);

        return '<div class="lexio-media-embed lexio-media-embed--' . $provider . '"'
            . ' style="position: relative; width: 100%; aspect-ratio: ' . $aspectRatio . ';">'
            . '<iframe src="' . $src . '" title="' . $title . '" loading="lazy"'
            . ' style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;"'
            . ' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"'
            . ' allowfullscreen></iframe>'
            . '</div>';
    }

    private function decodeUrl(string $url): string
    {
        return html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
