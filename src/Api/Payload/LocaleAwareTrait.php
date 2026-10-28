<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Payload;

use Symfony\Component\Serializer\Attribute\SerializedName;

trait LocaleAwareTrait
{
    #[SerializedName('locale')]
    private ?string $locale = null;

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

}
