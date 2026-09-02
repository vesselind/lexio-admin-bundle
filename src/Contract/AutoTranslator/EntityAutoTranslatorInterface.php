<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\AutoTranslator;

interface EntityAutoTranslatorInterface
{
    public function translateField(object $entity, string $field, string $currentLocale): bool;

    /**
     * Translates all Gedmo Translatable fields of the entity into every
     * configured locale (except the one currently being edited).
     */
    public function translateFields(object $entity, string $currentLocale): void;
}

