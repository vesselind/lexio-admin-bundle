<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Fields;

class EnumSelectOptionField extends BaseField
{
    /**
     * @param string        $enumClass         FQCN of the backed enum.
     * @param list<mixed>   $choices            Available choices.
     * @param string        $choiceLabel        Property name on the enum for the label (e.g. 'label').
     * @param string|null   $translationDomain  Optional Symfony translation domain.
     */
    public function __construct(
        private readonly string  $enumClass,
        private array            $choices,
        private readonly string  $choiceLabel,
        private readonly ?string $translationDomain = null,
    ) {
    }

    public function twigComponent(): ?string
    {
        return 'Admin:EnumSelectOption';
    }

    public function getValue(): mixed
    {
        return parent::getValue()->value;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getChoices(): array
    {
        if (empty($this->choices)) {
            return [];
        }

        $choices = [];
        foreach ($this->choices as $choice) {
            if (!$choice instanceof \BackedEnum) {
                throw new \RuntimeException('The choices must be an array of BackedEnum instances.');
            }

            $choices[$choice->value] = $choice->{$this->choiceLabel};
        }

        return $choices;
    }

    public function getTranslationDomain(): ?string
    {
        return $this->translationDomain;
    }

    public function getEnumClass(): string
    {
        return $this->enumClass;
    }
}

