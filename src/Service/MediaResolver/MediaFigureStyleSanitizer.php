<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\MediaResolver;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/** Retains only the numeric widths produced by CKEditor's resize controls. */
final readonly class MediaFigureStyleSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): array
    {
        return ['figure'];
    }

    public function getSupportedAttributes(): array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        $width = null;
        foreach (explode(';', $value) as $declaration) {
            if (preg_match('/^\s*width\s*:\s*(\d+(?:\.\d+)?(?:px|%|em|rem))\s*$/iD', $declaration, $matches)) {
                $width = strtolower($matches[1]);
            }
        }

        return null === $width ? null : 'width: ' . $width;
    }
}
