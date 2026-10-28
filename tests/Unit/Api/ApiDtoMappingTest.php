<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Api;

use Lexio\AdminBundle\Api\MapTransformer\FileUrlTransformer;
use Lexio\AdminBundle\Api\MapTransformer\PageContentItemsTransformer;
use Lexio\AdminBundle\Api\Output\FileOutput;
use Lexio\AdminBundle\Api\Output\ImageOutput;
use Lexio\AdminBundle\Api\Output\PageOutput;
use Lexio\AdminBundle\Entity\Page;
use Lexio\AdminBundle\Entity\ContentItem;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\File\FileManager;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableFile;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class ApiDtoMappingTest extends TestCase
{
    public function test_file_dto_maps_host_getters_dates_and_transformer_services(): void
    {
        $output = $this->mapper()->map(new PortableFile(), new FileOutput());
        self::assertSame(42, $output->id);
        self::assertSame('fixture.pdf', $output->originalName);
        self::assertSame('2025-01-01 00:00:00', $output->createdAt);
        self::assertSame('https://portable.test/uploads/portable/fixture.pdf', $output->url);
    }

    public function test_image_dto_maps_an_inherited_host_entity(): void
    {
        $output = $this->mapper()->map(new PortableImage(), new ImageOutput());
        self::assertSame(42, $output->id);
        self::assertSame('Portable image', $output->alt);
        self::assertSame('https://portable.test/uploads/portable/fixture.pdf', $output->url);
    }

    public function test_page_dto_maps_bundle_entity_timestamps(): void
    {
        $page = new Page();
        $page->setTitle('Portable page');
        $page->setCreatedAt(new \DateTime('2025-01-01'));
        $page->setUpdatedAt(new \DateTime('2025-01-02'));
        $output = $this->mapper()->map($page, new PageOutput());
        self::assertSame('Portable page', $output->title);
        self::assertSame('2025-01-01 00:00:00', $output->createdAt);
        self::assertSame('2025-01-02 00:00:00', $output->updatedAt);
    }

    public function test_content_items_are_mapped_from_loaded_values_in_name_order(): void
    {
        $page = new Page();
        $page->addContentItem((new ContentItem())->setName('title')->setType(ContentItemTypes::TEXT_INPUT)->setValue('English title'));
        $page->addContentItem((new ContentItem())->setName('subtitle')->setType(ContentItemTypes::TEXT_INPUT)->setValue('English subtitle'));

        $items = (new PageContentItemsTransformer())(null, $page, null);

        self::assertSame(['subtitle', 'title'], array_column($items, 'name'));
        self::assertSame(['English subtitle', 'English title'], array_column($items, 'value'));
        self::assertSame(ContentItemTypes::TEXT_INPUT, $items[0]->type);
    }

    private function mapper(): ObjectMapper
    {
        $locator = new Container();
        $locator->set(FileUrlTransformer::class, new FileUrlTransformer(new FileManager(new Filesystem(), __DIR__, 'https://portable.test')));

        return new ObjectMapper(
            propertyAccessor: PropertyAccess::createPropertyAccessor(),
            transformCallableLocator: $locator,
        );
    }
}
