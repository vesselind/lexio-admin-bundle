<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\AutoTranslator;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\TranslatableListener;
use Lexio\AdminBundle\Contract\AutoTranslator\AutoTranslatorInterface;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityAutoTranslator implements EntityAutoTranslatorInterface
{
    private ?object $entity          = null;
    private ?string $entityClassName = null;

    public function __construct(
        private readonly EntityManagerInterface  $entityManager,
        private readonly AutoTranslatorInterface $translator,
        private readonly string                  $defaultLocale,
        /** @var list<string> */
        private readonly array                   $locales,
    ) {
    }

    public function translateFields(object $entity, string $currentLocale): void
    {
        if (!$this->supports($currentLocale)) {
            return;
        }

        $translatableLocales = $this->getTranslatableLocales($currentLocale);

        $this->entity          = $entity;
        $this->entityManager->refresh($this->entity);
        $defaultLocaleEntity   = clone $this->entity;
        $this->entityClassName = $entity::class;

        $translatableFields = $this->getTranslatableFields();

        if (empty($translatableFields)) {
            return;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($translatableLocales as $locale) {
            foreach ($translatableFields as $field) {
                $defaultValue   = $accessor->getValue($defaultLocaleEntity, $field);
                $translatedText = $this->performTranslation($defaultValue, $locale);

                if ($translatedText) {
                    $accessor->setValue($this->entity, $field, $translatedText);

                    if (!method_exists($this->entity, 'setTranslatableLocale')) {
                        throw new \LogicException('Translatable entities must provide setTranslatableLocale().');
                    }

                    $this->entity->setTranslatableLocale($locale);
                }
            }

            $this->entityManager->persist($this->entity);
            $this->entityManager->flush();
        }
    }

    public function translateField(object $entity, string $field, string $currentLocale): bool
    {
        if (!$this->supports($currentLocale)) {
            return false;
        }

        $accessor            = PropertyAccess::createPropertyAccessor();
        $defaultValue        = $accessor->getValue($entity, $field);
        $translatableLocales = $this->getTranslatableLocales($currentLocale);

        foreach ($translatableLocales as $locale) {
            $translatedText = $this->performTranslation($defaultValue, $locale);

            if ($translatedText) {
                if (!method_exists($entity, 'setTranslatableLocale')) {
                    throw new \LogicException('Translatable entities must provide setTranslatableLocale().');
                }

                $entity->setTranslatableLocale($locale);
                $this->entityManager->refresh($entity);
                $accessor->setValue($entity, $field, $translatedText);
                $this->entityManager->flush();
            }
        }

        return true;
    }

    /** @return list<string> */
    private function getTranslatableFields(): array
    {
        if ($this->entityClassName === null || $this->entity === null) {
            return [];
        }

        $meta = $this->entityManager->getClassMetadata($this->entityClassName);
        $this->entityManager->getUnitOfWork()->computeChangeSet($meta, $this->entity);

        /** @var class-string $entityClassName */
        $entityClassName = $this->entityClassName;
        $config = (new TranslatableListener())
            ->getConfiguration($this->entityManager, $entityClassName);

        /** @var list<string> */
        return array_values($config['fields'] ?? []);
    }

    private function performTranslation(?string $text, string $locale): ?string
    {
        if (!$text) {
            return null;
        }

        $localeLang = match ($locale) {
            'en'    => 'en-US',
            'fr'    => 'fr-FR',
            'de'    => 'de-DE',
            'es'    => 'es-ES',
            'it'    => 'it-IT',
            default => null,
        };

        if ($localeLang === null) {
            return null;
        }

        return $this->translator->translate($text, $this->defaultLocale, $localeLang);
    }

    /** @return list<string> */
    private function getTranslatableLocales(string $currentLocale): array
    {
        return array_values(array_filter(
            $this->locales,
            fn (string $locale) => $locale !== $this->defaultLocale && $locale !== $currentLocale,
        ));
    }

    private function supports(string $currentLocale): bool
    {
        return $this->translator->isActivated() && $currentLocale === $this->defaultLocale;
    }
}


