<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\TranslationSynchronizationOptions;
use PHPUnit\Framework\TestCase;

final class TranslationSynchronizationOptionsTest extends TestCase
{
    public function test_admin_actions_are_never_allowed_in_production(): void
    {
        self::assertFalse($this->options('prod')->areAdminActionsAllowed());
        self::assertTrue($this->options('dev')->areAdminActionsAllowed());
    }

    public function test_basic_auth_credentials_must_be_configured_as_a_pair(): void
    {
        $this->expectException(\LogicException::class);

        new TranslationSynchronizationOptions(
            enabled: true,
            environment: 'dev',
            appSecret: 'secret',
            deployedAppUrl: 'https://example.test',
            apiPath: '/api/translations',
            authSalt: 'salt',
            signatureTtl: 300,
            timeout: 30,
            maxPackageBytes: 1024,
            maxFiles: 10,
            basicAuthUsername: 'user',
            basicAuthPassword: null,
        );
    }

    private function options(string $environment): TranslationSynchronizationOptions
    {
        return new TranslationSynchronizationOptions(
            enabled: true,
            environment: $environment,
            appSecret: 'secret',
            deployedAppUrl: 'https://example.test',
            apiPath: '/api/translations',
            authSalt: 'salt',
            signatureTtl: 300,
            timeout: 30,
            maxPackageBytes: 1024,
            maxFiles: 10,
            basicAuthUsername: null,
            basicAuthPassword: null,
        );
    }
}
