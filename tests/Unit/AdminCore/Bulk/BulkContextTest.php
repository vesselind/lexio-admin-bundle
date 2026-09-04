<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\AdminCore\Bulk;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Lexio\AdminBundle\AdminCore\Bulk\BulkContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class BulkContextTest extends TestCase
{
    public function test_it_uses_the_controller_entity_instead_of_a_client_supplied_class_name(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create(
            '/admin/bulk-delete?ids[]=4&ids[]=9&entityFqcn=DateTimeImmutable',
            'POST',
            server: ['HTTP_X_CSRF_TOKEN' => 'valid-token'],
        ));

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::once())
            ->method('isTokenValid')
            ->with(self::callback(
                static fn (CsrfToken $token): bool => $token->getId() === 'bulk_action'
                    && $token->getValue() === 'valid-token',
            ))
            ->willReturn(true);

        $first = new BulkContextTestEntity();
        $second = new BulkContextTestEntity();
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(self::once())
            ->method('findBy')
            ->with(['id' => [4, 9]])
            ->willReturn([$first, $second]);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->expects(self::once())
            ->method('getRepository')
            ->with(BulkContextTestEntity::class)
            ->willReturn($repository);

        $context = new BulkContext($requestStack, $manager, $csrfTokenManager);

        self::assertSame([$first, $second], $context->getEntities(BulkContextTestEntity::class));
        self::assertSame([4, 9], $context->getIds());
    }

    public function test_it_rejects_bulk_operations_without_a_valid_csrf_token(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create(
            '/admin/bulk-delete?ids[]=4',
            'POST',
            server: ['HTTP_X_CSRF_TOKEN' => 'invalid-token'],
        ));

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->expects(self::once())->method('isTokenValid')->willReturn(false);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::never())->method('getRepository');

        $context = new BulkContext($requestStack, $manager, $csrfTokenManager);

        $this->expectException(AccessDeniedHttpException::class);
        $context->getEntities(BulkContextTestEntity::class);
    }
}

final class BulkContextTestEntity
{
}
