<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\AutoTranslator;

interface AutoTranslatorInterface
{
    public function isActivated(): bool;

    public function translate(string $text, string $fromLocale, string $toLocale): string;
}

