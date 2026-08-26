<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Translation;

final readonly class TranslationEntry
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }
}
