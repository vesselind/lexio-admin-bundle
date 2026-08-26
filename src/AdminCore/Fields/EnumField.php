<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

class EnumField extends BaseField
{
    public function __construct(
        private readonly string $translationDomain = 'admin',
    ) {
    }

    public function getColor(): ?string
    {
        $enum = $this->getValue();

        if (!$enum) {
            return null;
        }

        if (method_exists($enum, 'color')) {
            return $enum->color();
        }

        return 'info';
    }

    public function getTranslationKey(): ?string
    {
        $enum = $this->getValue();

        if (!$enum) {
            return null;
        }

        if (method_exists($enum, 'getTranslationKey')) {
            return $enum->getTranslationKey();
        }

        return $enum->name;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }
}

