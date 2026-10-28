<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Api;

use Lexio\AdminBundle\Api\Service\ApiKeyGenerator;
use Lexio\AdminBundle\Api\Service\ApiKeyIssuer;
use Lexio\AdminBundle\Contract\Api\ApiKeyUserProviderInterface;
use Lexio\AdminBundle\Security\ApiKeyHandler;
use Lexio\AdminBundle\Tests\Fixtures\Api\PortableUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class ApiKeyServicesTest extends TestCase
{
    public function test_issue_rotates_a_portable_user_and_returns_only_the_raw_secret(): void
    {
        $user = (new PortableUser())->setApiKeyHash('previous-hash');
        $users = $this->createMock(ApiKeyUserProviderInterface::class);
        $users->expects(self::once())->method('findByIdentifier')->with($user->getUserIdentifier())->willReturn($user);
        $users->expects(self::once())->method('save')->with($user);
        $users->expects(self::once())->method('flush');
        $generator = new ApiKeyGenerator('https://portable.example.test');
        $rawKey = (new ApiKeyIssuer($users, $generator))->issue($user->getUserIdentifier());

        self::assertNotNull($rawKey);
        self::assertMatchesRegularExpression('/^portable_example_[a-f0-9]{32}$/', $rawKey);
        self::assertSame(hash('sha256', $rawKey), $user->getApiKeyHash());
        self::assertNotSame($rawKey, $user->getApiKeyHash());
    }

    public function test_unknown_identifier_does_not_write_credentials(): void
    {
        $users = $this->createMock(ApiKeyUserProviderInterface::class);
        $users->method('findByIdentifier')->willReturn(null);
        $users->expects(self::never())->method('save');
        $users->expects(self::never())->method('flush');
        self::assertNull((new ApiKeyIssuer($users, new ApiKeyGenerator('')))->issue('missing'));
    }

    public function test_registration_preserves_existing_credentials(): void
    {
        $user = (new PortableUser())->setApiKeyHash('existing-hash');
        $users = $this->createMock(ApiKeyUserProviderInterface::class);
        $users->method('findByIdentifier')->willReturn($user);
        $users->expects(self::never())->method('save');
        $users->expects(self::never())->method('flush');
        (new ApiKeyIssuer($users, new ApiKeyGenerator('')))->ensureKey($user->getUserIdentifier());
        self::assertSame('existing-hash', $user->getApiKeyHash());
    }

    public function test_backfill_accepts_an_iterator_and_assigns_missing_keys(): void
    {
        $first = new PortableUser('first');
        $second = new PortableUser('second');
        $users = $this->createMock(ApiKeyUserProviderInterface::class);
        $users->method('findWithoutKey')->willReturn(new \ArrayIterator([$first, $second]));
        $users->expects(self::exactly(2))->method('save');
        $users->expects(self::once())->method('flush');
        self::assertSame(2, (new ApiKeyIssuer($users, new ApiKeyGenerator('')))->backfill());
        self::assertNotNull($first->getApiKeyHash());
        self::assertNotSame($first->getApiKeyHash(), $second->getApiKeyHash());
    }

    public function test_handler_resolves_a_portable_user_by_hash(): void
    {
        $user = new PortableUser();
        $users = $this->createMock(ApiKeyUserProviderInterface::class);
        $users->expects(self::once())->method('findByHash')->with(hash('sha256', 'fixture-key'))->willReturn($user);
        $badge = (new ApiKeyHandler($users, new ApiKeyGenerator('')))->getUserBadgeFrom('fixture-key');
        self::assertSame($user->getUserIdentifier(), $badge->getUserIdentifier());
        self::assertSame($user, $badge->getUser());
    }

    public function test_handler_rejects_unknown_credentials(): void
    {
        $users = $this->createStub(ApiKeyUserProviderInterface::class);
        $users->method('findByHash')->willReturn(null);
        $this->expectException(BadCredentialsException::class);
        (new ApiKeyHandler($users, new ApiKeyGenerator('')))->getUserBadgeFrom('missing');
    }
}
