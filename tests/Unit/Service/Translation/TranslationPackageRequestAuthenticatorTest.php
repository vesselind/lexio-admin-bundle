<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\TranslationPackageRequestAuthenticator;
use Lexio\AdminBundle\Service\Translation\TranslationSynchronizationOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class TranslationPackageRequestAuthenticatorTest extends TestCase
{
    public function test_it_accepts_an_untampered_signature_inside_the_ttl(): void
    {
        $authenticator = $this->authenticator();
        $headers = $authenticator->createHeaders('POST', '/api/translations/upload', 'archive');

        self::assertTrue($authenticator->isValid(
            'POST',
            '/api/translations/upload',
            'archive',
            $headers[TranslationPackageRequestAuthenticator::TIMESTAMP_HEADER],
            $headers[TranslationPackageRequestAuthenticator::SIGNATURE_HEADER],
        ));
    }

    public function test_it_rejects_tampering_and_expired_signatures(): void
    {
        $clock = new MockClock('2025-01-01 12:00:00 UTC');
        $authenticator = $this->authenticator($clock);
        $headers = $authenticator->createHeaders('POST', '/api/translations/upload', 'archive');

        self::assertFalse($authenticator->isValid(
            'POST',
            '/api/translations/upload',
            'tampered',
            $headers[TranslationPackageRequestAuthenticator::TIMESTAMP_HEADER],
            $headers[TranslationPackageRequestAuthenticator::SIGNATURE_HEADER],
        ));

        $clock->sleep(301);
        self::assertFalse($authenticator->isValid(
            'POST',
            '/api/translations/upload',
            'archive',
            $headers[TranslationPackageRequestAuthenticator::TIMESTAMP_HEADER],
            $headers[TranslationPackageRequestAuthenticator::SIGNATURE_HEADER],
        ));
    }

    private function authenticator(?MockClock $clock = null): TranslationPackageRequestAuthenticator
    {
        $options = new TranslationSynchronizationOptions(
            enabled: true,
            environment: 'test',
            appSecret: 'app-secret',
            deployedAppUrl: 'https://example.test',
            apiPath: '/api/translations',
            authSalt: 'sync-salt',
            signatureTtl: 300,
            timeout: 30,
            maxPackageBytes: 1048576,
            maxFiles: 20,
            basicAuthUsername: null,
            basicAuthPassword: null,
        );

        return new TranslationPackageRequestAuthenticator($options, $clock ?? new MockClock('2025-01-01 12:00:00 UTC'));
    }
}
