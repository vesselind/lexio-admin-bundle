<?php

namespace Lexio\AdminBundle\Page;

use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
use Lexio\AdminBundle\Attributes\FieldType;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Entity\Page;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

readonly class PageManager implements PageManagerInterface, PageAdministrationInterface
{
    public function __construct(private EntityManagerInterface        $manager,
                                private EntityAutoTranslatorInterface $autoTranslator,
                                private string                        $defaultLocale)
    {
    }

    public function createOrUpdatePage(BasePage $page, ?string $locale = null): void
    {
        $pageEntity = $this->manager->getRepository(Page::class)->findOneBy(['name' => get_class($page)]);


        if (!$pageEntity) {
            $pageEntity = new Page();
            $this->manager->persist($pageEntity);
        }

        if ($locale === null) {
            $locale = $this->defaultLocale;
        }

        if ($locale !== $this->defaultLocale) {
            $pageEntity->setTranslatableLocale($locale);
            $this->manager->refresh($pageEntity);
        }

        $pageEntity->setTitle($page->getTitle());

        $pageEntity->setName(get_class($page));

        $pageReflection = new \ReflectionClass($page);

        $pageProperties = $pageReflection->getProperties();

        foreach ($pageProperties as $pageProperty) {

            $attributes = $pageProperty->getAttributes();

            if (!$attributes) {
                continue;
            }

            $attribute = $attributes[0];

            $propertyName = $pageProperty->getName();
            $type = $attribute->getArguments()[0] ?? null;

            if (!$type instanceof ContentItemTypes) {
                throw new \LogicException(sprintf(
                    'The %s attribute on %s::$%s must contain a %s value.',
                    FieldType::class,
                    $page::class,
                    $propertyName,
                    ContentItemTypes::class,
                ));
            }

            $contentItem = $this->manager->getRepository(ContentItem::class)->findOneBy(['page' => $pageEntity, 'name' => $propertyName]);

            if (!$contentItem) {
                $contentItem = new ContentItem();
                $this->manager->persist($contentItem);
            }

            $contentItem
                ->setPage($pageEntity)
                ->setName($propertyName)
                ->setType($type);


            if ($type === ContentItemTypes::IMAGE) {
                if ($locale === $this->defaultLocale) {
                    $image = $this->accessor()->getValue($page, $propertyName);

                    if ($image !== null && !$image instanceof ImageEntityInterface) {
                        throw new \LogicException(sprintf(
                            'The image field %s::$%s must contain an %s or null.',
                            $page::class,
                            $propertyName,
                            ImageEntityInterface::class,
                        ));
                    }

                    $contentItem
                        ->setImage($image)
                        ->setValue(null);
                }
            } elseif ($locale === $this->defaultLocale) {
                $value = $this->accessor()->getValue($page, $propertyName);

                if ($value !== null && !is_string($value)) {
                    throw new \LogicException(sprintf(
                        'The content field %s::$%s must contain a string or null.',
                        $page::class,
                        $propertyName,
                    ));
                }

                $contentItem->setValue($value);
                $this->manager->flush();

                $this->autoTranslator->translateField($contentItem, 'value', $locale);
            } else {
                $contentItem->setTranslatableLocale($locale);
                $this->manager->refresh($contentItem);
                $value = $this->accessor()->getValue($page, $propertyName);

                if ($value !== null && !is_string($value)) {
                    throw new \LogicException(sprintf(
                        'The content field %s::$%s must contain a string or null.',
                        $page::class,
                        $propertyName,
                    ));
                }

                $contentItem->setValue($value);
            }

            $this->manager->flush();
        }

        $this->manager->flush();
    }


    public function getPageObject(string $pageNameFqcn, ?string $locale = null): ?BasePage
    {

        if ($locale === null) {
            $locale = $this->defaultLocale;
        }

        $pageEntity = $this->manager->getRepository(Page::class)->findOneBy(['name' => $pageNameFqcn]);

        if (!$pageEntity) {
            return null;
        }

        $pageEntity->setTranslatableLocale($locale);

        $this->manager->refresh($pageEntity);

        $contentItems = $pageEntity->getContentItems();

        $pageInstance = new $pageNameFqcn();

        if (!$pageInstance instanceof BasePage) {
            throw new \LogicException(sprintf('Page class "%s" must extend %s.', $pageNameFqcn, BasePage::class));
        }

        foreach ($contentItems as $contentItem) {

            $contentItem->setTranslatableLocale($locale);
            $this->manager->refresh($contentItem);

            $propertyName = $contentItem->getName();
            $value = $contentItem->getValue();
            $type = $contentItem->getType();

            if ($propertyName === null) {
                throw new \LogicException('A content item must have a property name.');
            }

            if ($type === ContentItemTypes::IMAGE) {
                $value = $contentItem->getImage();
            }

            $this->accessor()->setValue($pageInstance, $propertyName, $value);
        }

        //clone page entity into page class

        $pageInstance->setId($pageEntity->getId());


        return $pageInstance;
    }

    public function accessor(): PropertyAccessor
    {
        return PropertyAccess::createPropertyAccessor();
    }
}
