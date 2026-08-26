<?php

namespace Lexio\AdminBundle\Entity\Traits;

use Gedmo\Mapping\Annotation as Gedmo;

trait TranslatableEntity
{
    #[Gedmo\Locale]
    protected $locale;

    /* Unmapped field */
    private bool $autoUpdateTranslation = false;

    public function setTranslatableLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function isAutoUpdateTranslation(): bool
    {
        return $this->autoUpdateTranslation;
    }

    public function setAutoUpdateTranslation(bool $autoUpdateTranslation): void
    {
        $this->autoUpdateTranslation = $autoUpdateTranslation;
    }


}
