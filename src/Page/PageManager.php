<?php

namespace Lexio\AdminBundle\Page;

use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Lexio\AdminBundle\Contract\AutoTranslator\EntityAutoTranslatorInterface;
use Lexio\AdminBundle\Contract\Page\PageAdministrationInterface;
use Lexio\AdminBundle\Contract\Page\PageManagerInterface;
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
        $translationRepository = $this->manager->getRepository(Translation::class);

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
            $type = $attribute->getArguments()[0];

            $contentItem = $this->manager->getRepository(ContentItem::class)->findOneBy(['page' => $pageEntity, 'name' => $propertyName]);

            if (!$contentItem) {
                $contentItem = new ContentItem();
                $this->manager->persist($contentItem);
            }

            $contentItem
                ->setPage($pageEntity)
                ->setName($propertyName)
                ->setType($type);


            if ($locale === $this->defaultLocale) {
                $contentItem->setValue($this->accessor()->getValue($page, $propertyName));
                $this->manager->flush();

                $this->autoTranslator->translateField($contentItem, 'value', $locale);
            } else {
                $contentItem->setTranslatableLocale($locale);
                $this->manager->refresh($contentItem);
                $contentItem->setValue($this->accessor()->getValue($page, $propertyName));
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

            if ($propertyName === null) {
                throw new \LogicException('A content item must have a property name.');
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
