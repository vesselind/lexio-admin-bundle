<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Service;

use Lexio\AdminBundle\Exception\ApiValidationException;
use Lexio\AdminBundle\Attributes\FieldType;
use Lexio\AdminBundle\Contract\File\FileRepositoryInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Page\BasePage;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Applies API-provided content item values onto a page object before persistence.
 *
 * Unknown field names and values that do not match the field type are rejected
 * with a 422 referencing the offending `content_items.<name>` key.
 */
final readonly class PageContentItemUpdater
{
    public function __construct(private FileRepositoryInterface $fileRepository)
    {
    }

    /**
     * @param array<string, string|int|null> $contentItems
     */
    public function apply(BasePage $page, array $contentItems): void
    {
        $fieldTypes = $this->fieldTypes($page);
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($contentItems as $name => $value) {
            $type = $fieldTypes[$name] ?? null;

            if ($type === null) {
                throw ApiValidationException::field(
                    sprintf('content_items.%s', $name),
                    'The content item does not exist on this page.'
                );
            }

            if ($type === ContentItemTypes::IMAGE) {
                $accessor->setValue($page, $name, $this->resolveImage($name, $value));

                continue;
            }

            if ($value !== null && !is_string($value)) {
                throw ApiValidationException::field(
                    sprintf('content_items.%s', $name),
                    'This value should be of type string or null.'
                );
            }

            $accessor->setValue($page, $name, $value);
        }
    }

    private function resolveImage(string $name, mixed $value): ?ImageEntityInterface
    {
        if ($value === null) {
            return null;
        }

        if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
            throw ApiValidationException::field(
                sprintf('content_items.%s', $name),
                'This value should be a positive image ID or null.'
            );
        }

        $id = (int) $value;

        if ($id < 1) {
            throw ApiValidationException::field(
                sprintf('content_items.%s', $name),
                'This value should be a positive image ID or null.'
            );
        }

        $image = $this->fileRepository->findById($id);

        if (!$image instanceof ImageEntityInterface) {
            throw ApiValidationException::field(
                sprintf('content_items.%s', $name),
                sprintf('Image with ID %d was not found.', $id)
            );
        }

        return $image;
    }

    /**
     * @return array<string, ContentItemTypes>
     */
    private function fieldTypes(BasePage $page): array
    {
        $types = [];

        foreach ((new \ReflectionClass($page))->getProperties() as $property) {
            foreach ($property->getAttributes(FieldType::class) as $attribute) {
                $types[$property->getName()] = $attribute->newInstance()->contentItemType;
            }
        }

        return $types;
    }
}
