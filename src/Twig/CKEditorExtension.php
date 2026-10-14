<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Twig;

use Lexio\AdminBundle\Service\MediaResolver\MediaEmbedRenderer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Renders CKEditor content with playable media embeds.
 */
final class CKEditorExtension extends AbstractExtension
{
    public function __construct(
        private readonly MediaEmbedRenderer $mediaEmbedRenderer,
        private readonly HtmlSanitizerInterface $contentSanitizer,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('ckeditor_content', $this->renderContent(...), ['is_safe' => ['html']]),
        ];
    }

    public function renderContent(?string $content): Markup
    {
        return new Markup($this->mediaEmbedRenderer->render($this->contentSanitizer->sanitize($content ?? '')), 'UTF-8');
    }
}
