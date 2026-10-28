<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\MapTransformer;

use Lexio\AdminBundle\Api\Output\ContentItemOutput;
use Lexio\AdminBundle\Entity\Page;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * Resolves a page's content items in the currently selected locale for API responses.
 *
 * @implements TransformCallableInterface<object, object>
 */
final readonly class PageContentItemsTransformer implements TransformCallableInterface
{
    /**
     * @return list<ContentItemOutput>
     */
    public function __invoke(mixed $value, object $source, ?object $target): array
    {
        if (!$source instanceof Page) {
            throw new \RuntimeException(sprintf('PageContentItemsTransformer can only transform page entities, got %s', get_debug_type($source)));
        }

        $items = [];

        foreach ($source->getContentItems() as $contentItem) {
            $item = new ContentItemOutput();
            $item->id = $contentItem->getId();
            $item->name = $contentItem->getName();
            $item->type = $contentItem->getType();
            $item->value = $contentItem->getValue();
            $item->imageId = $contentItem->getImage()?->getId();

            $items[] = $item;
        }

        usort($items, static fn (ContentItemOutput $a, ContentItemOutput $b): int => strcmp((string) $a->name, (string) $b->name));

        return $items;
    }
}
