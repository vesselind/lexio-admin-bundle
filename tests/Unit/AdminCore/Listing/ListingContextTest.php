<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Listing;

use Doctrine\ORM\Mapping\ClassMetadata;
use Lexio\AdminBundle\AdminCore\Breadcrumbs\AdminBreadcrumbs;
use Lexio\AdminBundle\AdminCore\Fields\TitleField;
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
            $entityManager,
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
            $entityManager,
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
}
