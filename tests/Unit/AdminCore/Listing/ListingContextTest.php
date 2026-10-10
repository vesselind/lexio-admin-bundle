<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Listing;

use Doctrine\ORM\Mapping\ClassMetadata;
use Lexio\AdminBundle\AdminCore\Breadcrumbs\AdminBreadcrumbs;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
use Lexio\AdminBundle\AdminCore\Listing\EntitySortabilityPropertiesResolver;
use Lexio\AdminBundle\AdminCore\Listing\ListingContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

final class ListingContextTest extends TestCase
{
    public function test_entity_fqcn_can_be_set_before_columns_without_losing_sortability(): void
    {
        $request = Request::create('/admin/invoice');
        $request->attributes->set('_route', 'admin.invoice.index');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->fieldNames = ['title'];

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        $context = new ListingContext(
            $requestStack,
            $this->createStub(RouterInterface::class),
            new EntitySortabilityPropertiesResolver($entityManager),
            $this->createStub(PaginatorInterface::class),
            $this->createStub(AdminBreadcrumbs::class),
        );

        $context
            ->setEntityFqcn(\stdClass::class)
            ->addColumn('title', new TitleField())
            ->addColumn('virtual', new TitleField());

        self::assertTrue($context->getColumns()['title']->isSortable());
        self::assertFalse($context->getColumns()['virtual']->isSortable());
        self::assertSame(\stdClass::class, $context->getColumns()['title']->getEntityFqcn());
        self::assertSame(\stdClass::class, $context->getColumns()['virtual']->getEntityFqcn());
    }

    public function test_entity_fqcn_can_still_be_set_after_columns(): void
    {
        $request = Request::create('/admin/invoice');
        $request->attributes->set('_route', 'admin.invoice.index');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->fieldNames = ['title'];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with(\stdClass::class)->willReturn($metadata);
        $context = new ListingContext(
            $requestStack,
            $this->createStub(RouterInterface::class),
            new EntitySortabilityPropertiesResolver($entityManager),
            $this->createStub(PaginatorInterface::class),
            $this->createStub(AdminBreadcrumbs::class),
        );

        $context
            ->addColumn('title', new TitleField())
            ->addColumn('virtual', new TitleField())
            ->setEntityFqcn(\stdClass::class);

        self::assertTrue($context->getColumns()['title']->isSortable());
        self::assertFalse($context->getColumns()['virtual']->isSortable());
        self::assertSame(\stdClass::class, $context->getColumns()['title']->getEntityFqcn());
    }

    public function test_sortability_matches_mapped_properties_instead_of_database_columns_or_associations(): void
    {
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->mapField(['fieldName' => 'title', 'columnName' => 'title_text', 'type' => 'string']);
        $metadata->mapManyToOne(['fieldName' => 'category', 'targetEntity' => \stdClass::class]);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn($metadata);
        $context = $this->createContext($entityManager);

        $context
            ->addColumn('title', new TitleField())
            ->addColumn('title_text', new TitleField())
            ->addColumn('Title', new TitleField())
            ->addColumn('category', new TitleField())
            ->addColumn('virtual', new TitleField())
            ->setEntityFqcn(\stdClass::class);

        self::assertTrue($context->getColumns()['title']->isSortable());
        self::assertFalse($context->getColumns()['title_text']->isSortable());
        self::assertFalse($context->getColumns()['Title']->isSortable());
        self::assertFalse($context->getColumns()['category']->isSortable());
        self::assertFalse($context->getColumns()['virtual']->isSortable());
    }

    public function test_refresh_clears_sortability_when_no_mapped_properties_remain_and_returns_the_context(): void
    {
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->mapField(['fieldName' => 'title', 'type' => 'string']);
        $emptyMetadata = new ClassMetadata(\stdClass::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturnOnConsecutiveCalls($metadata, $emptyMetadata);
        $context = $this->createContext($entityManager);
        $context
            ->addColumn('title', new TitleField())
            ->addColumn('virtual', new TitleField())
            ->setEntityFqcn(\stdClass::class);
        self::assertTrue($context->getColumns()['title']->isSortable());
        $context->getColumns()['virtual']->setSortable(true);

        self::assertSame($context, $context->refreshColumnsSortability());

        self::assertFalse($context->getColumns()['title']->isSortable());
        self::assertFalse($context->getColumns()['virtual']->isSortable());
    }

    public function test_changing_entity_recalculates_column_sortability(): void
    {
        $firstMetadata = new ClassMetadata(\stdClass::class);
        $firstMetadata->mapField(['fieldName' => 'title', 'type' => 'string']);
        $secondMetadata = new ClassMetadata(\ArrayObject::class);
        $secondMetadata->mapField(['fieldName' => 'virtual', 'type' => 'string']);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturnMap([
            [\stdClass::class, $firstMetadata],
            [\ArrayObject::class, $secondMetadata],
        ]);
        $context = $this->createContext($entityManager);
        $context
            ->addColumn('title', new TitleField())
            ->addColumn('virtual', new TitleField())
            ->setEntityFqcn(\stdClass::class);
        self::assertTrue($context->getColumns()['title']->isSortable());
        self::assertFalse($context->getColumns()['virtual']->isSortable());

        $context->setEntityFqcn(\ArrayObject::class);

        self::assertFalse($context->getColumns()['title']->isSortable());
        self::assertTrue($context->getColumns()['virtual']->isSortable());
        self::assertSame(\ArrayObject::class, $context->getColumns()['title']->getEntityFqcn());
        self::assertSame(\ArrayObject::class, $context->getColumns()['virtual']->getEntityFqcn());
    }

    public function test_refresh_requires_an_entity_before_reading_metadata(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getClassMetadata');
        $context = $this->createContext($entityManager);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Entity FQCN not set. Call setEntityFqcn() first.');

        $context->refreshColumnsSortability();
    }

    public function test_metadata_errors_are_propagated_when_resolving_sortability(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willThrowException(new \RuntimeException('Metadata unavailable.'));
        $context = $this->createContext($entityManager);
        $context->addColumn('title', new TitleField());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Metadata unavailable.');

        $context->setEntityFqcn(\stdClass::class);
    }

    private function createContext(EntityManagerInterface $entityManager): ListingContext
    {
        return new ListingContext(
            new RequestStack(),
            $this->createStub(RouterInterface::class),
            new EntitySortabilityPropertiesResolver($entityManager),
            $this->createStub(PaginatorInterface::class),
            $this->createStub(AdminBreadcrumbs::class),
        );
    }
}
