<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Translation;

final readonly class TranslationFile
{
    public function __construct(
        public string $domain,
        public string $locale,
        public string $filename,
    ) {
    }
}
