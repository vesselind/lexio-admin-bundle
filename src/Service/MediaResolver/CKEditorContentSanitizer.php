<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/** Safe stored rich text, including semantic embeds and validated figure widths. */
final readonly class CKEditorContentSanitizer implements HtmlSanitizerInterface
{
    private HtmlSanitizerInterface $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowMediaSchemes(['http', 'https'])
            ->allowElement('oembed', ['url'])
            ->allowAttribute('class', ['figure'])
            ->allowAttribute('style', ['figure'])
            ->withAttributeSanitizer(new MediaFigureStyleSanitizer())
            ->withMaxInputLength(-1);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $input): string
    {
        return $this->sanitizer->sanitize($input);
    }

    public function sanitizeFor(string $element, string $input): string
    {
        return $this->sanitizer->sanitizeFor($element, $input);
    }
}
