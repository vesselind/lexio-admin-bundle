<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Enum;

use Lexio\AdminBundle\Utils\AdminUtils;

/**
 * Mix this trait into a string-backed enum to get a Symfony-style translation key.
 *
 * Example key:  "settings.image_max_size"  (for Settings::IMAGE_MAX_SIZE)
 */
trait TranslatableEnum
{
    public function getTranslationKey(): string
    {
        return AdminUtils::classNameToSnake(self::class) . '.' . mb_strtolower($this->name);
    }
}

