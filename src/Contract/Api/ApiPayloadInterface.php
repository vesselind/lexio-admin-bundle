<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Api;


interface ApiPayloadInterface
{
    public function getLocale(): ?string;

    public function setLocale(?string $locale): void;
}
