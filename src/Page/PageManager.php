<?php

declare(strict_types=1);

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
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * @phpstan-type PageContentData array{
 *     id: int|null,
 *     fields: array<string, string|null|array{class: class-string<ImageEntityInterface>, id: int}>
 * }
 */
readonly class PageManager implements PageManagerInterface, PageAdministrationInterface
{
    public const CACHE_TAG = 'lexio_admin.pages';

    public function __construct(
        private EntityManagerInterface $manager,
        private EntityAutoTranslatorInterface $autoTranslator,
        private string $defaultLocale,
        private ?TagAwareCacheInterface $cache = null,
        private int $cacheLifetime = 3600,
    ) {
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


            $this->persistContentItemField($contentItem, $page, $propertyName, $type, $locale);

            $this->manager->flush();
        }

        $this->manager->flush();
    }

    private function persistContentItemField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        ContentItemTypes $type,
        string $locale,
    ): void {
        match (true) {
            $type === ContentItemTypes::IMAGE => $this->persistImageField($contentItem, $page, $propertyName, $locale),
            !$type->translatable() => $this->persistNonTranslatableField($contentItem, $page, $propertyName, $locale),
            default => $this->persistTranslatableField($contentItem, $page, $propertyName, $locale),
        };
    }

    private function persistImageField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        string $locale,
    ): void {
        if ($locale !== $this->defaultLocale) {
            return;
        }

        $contentItem
            ->setImage($this->getImageFieldValue($page, $propertyName))
            ->setValue(null);
    }

    private function persistNonTranslatableField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        string $locale,
    ): void {
        if ($locale !== $this->defaultLocale) {
            return;
        }

        $contentItem
            ->setImage(null)
            ->setValue($this->getStringFieldValue($page, $propertyName));
    }

    private function persistTranslatableField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        string $locale,
    ): void {
        match ($locale === $this->defaultLocale) {
            true => $this->persistDefaultLocaleField($contentItem, $page, $propertyName, $locale),
            false => $this->persistLocalizedField($contentItem, $page, $propertyName, $locale),
        };
    }

    private function persistDefaultLocaleField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        string $locale,
    ): void {
        $contentItem->setValue($this->getStringFieldValue($page, $propertyName));
        $this->manager->flush();
        $this->autoTranslator->translateField($contentItem, 'value', $locale);
    }

    private function persistLocalizedField(
        ContentItem $contentItem,
        BasePage $page,
        string $propertyName,
        string $locale,
    ): void {
        $contentItem->setTranslatableLocale($locale);
        $this->manager->refresh($contentItem);
        $contentItem->setValue($this->getStringFieldValue($page, $propertyName));
    }

    private function getImageFieldValue(BasePage $page, string $propertyName): ?ImageEntityInterface
    {
        $image = $this->accessor()->getValue($page, $propertyName);

        if ($image !== null && !$image instanceof ImageEntityInterface) {
            throw new \LogicException(sprintf(
                'The image field %s::$%s must contain an %s or null.',
                $page::class,
                $propertyName,
                ImageEntityInterface::class,
            ));
        }

        return $image;
    }

    private function getStringFieldValue(BasePage $page, string $propertyName): ?string
    {
        $value = $this->accessor()->getValue($page, $propertyName);

        if ($value !== null && !is_string($value)) {
            throw new \LogicException(sprintf(
                'The content field %s::$%s must contain a string or null.',
                $page::class,
                $propertyName,
            ));
        }

        return $value;
    }

    public function getPageObject(string $pageNameFqcn, ?string $locale = null): ?BasePage
    {
        $locale ??= $this->defaultLocale;

        /** @var PageContentData|null $content */
        $content = $this->cache === null
            ? $this->loadPageContent($pageNameFqcn, $locale)
            : $this->cache->get('page.v2.' . hash('sha256', $pageNameFqcn . "\0" . $locale),
                function (ItemInterface $item) use ($pageNameFqcn, $locale): ?array {
                    $item->expiresAfter($this->cacheLifetime);
                    $item->tag(self::CACHE_TAG);

                    return $this->loadPageContent($pageNameFqcn, $locale);
                });
        if ($content === null) {
            return null;
        }

        $pageInstance = new $pageNameFqcn();

        if (!$pageInstance instanceof BasePage) {
            throw new \LogicException(sprintf('Page class "%s" must extend %s.', $pageNameFqcn, BasePage::class));
        }

        $accessor = $this->accessor();
        foreach ($content['fields'] as $propertyName => $value) {
            if (is_array($value)) {
                $value = $this->manager->getReference($value['class'], $value['id']);
                if (!$value instanceof ImageEntityInterface) {
                    throw new \LogicException('A page image reference must resolve to an image entity.');
                }
            }

            $accessor->setValue($pageInstance, $propertyName, $value);
        }

        return $pageInstance->setId($content['id']);
    }

    /** @return PageContentData|null */
    private function loadPageContent(string $pageClass, string $locale): ?array
    {
        $page = $this->manager->getRepository(Page::class)->findOneBy(['name' => $pageClass]);
        if ($page === null) {
            return null;
        }

        $page->setTranslatableLocale($locale);
        $this->manager->refresh($page);

        $fields = [];
        foreach ($page->getContentItems() as $contentItem) {
            $contentItem->setTranslatableLocale($locale);
            $this->manager->refresh($contentItem);

            $name = $contentItem->getName();
            if ($name === null) {
                throw new \LogicException('A content item must have a property name.');
            }

            $value = $contentItem->getValue();
            if ($contentItem->getType() === ContentItemTypes::IMAGE) {
                $image = $contentItem->getImage();
                $value = null;
                if ($image !== null) {
                    $id = $image->getId();
                    if ($id === null) {
                        throw new \LogicException('A page image must be persisted before it can be referenced.');
                    }

                    // Cache identifiers instead of serializing Doctrine entities or proxies.
                    /** @var class-string<ImageEntityInterface> $imageClass */
                    $imageClass = $this->manager->getClassMetadata($image::class)->getName();
                    $value = ['class' => $imageClass, 'id' => $id];
                }
            }

            $fields[$name] = $value;
        }

        return ['id' => $page->getId(), 'fields' => $fields];
    }

    public function accessor(): PropertyAccessor
    {
        return PropertyAccess::createPropertyAccessor();
    }
}
