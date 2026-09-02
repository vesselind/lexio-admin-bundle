<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Translation;

use Lexio\AdminBundle\Service\Translation\TranslationPackageHttpClient;
use Lexio\AdminBundle\Service\Translation\TranslationPackageRequestAuthenticator;
use Lexio\AdminBundle\Service\Translation\TranslationSynchronizationOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class TranslationPackageHttpClientTest extends TestCase
{
    public function test_it_uploads_to_the_configured_url_with_basic_auth_and_signature(): void
    {
        $options = $this->options('user', 'password');
        $client = new MockHttpClient(function (string $method, string $url, array $requestOptions): JsonMockResponse {
            self::assertSame('POST', $method);
            self::assertSame('https://example.test/api/translations/upload', $url);
            self::assertSame(
                'Authorization: Basic ' . base64_encode('user:password'),
                $requestOptions['normalized_headers']['authorization'][0],
            );
            self::assertSame('archive', $requestOptions['body']);
            self::assertArrayHasKey('x-lexio-timestamp', $requestOptions['normalized_headers']);
            self::assertArrayHasKey('x-lexio-signature', $requestOptions['normalized_headers']);

            return new JsonMockResponse([
                'files_created' => 1,
                'files_updated' => 2,
                'keys_inserted' => 3,
                'keys_updated' => 4,
                'keys_unchanged' => 5,
            ]);
        });
        $transport = new TranslationPackageHttpClient(
            $client,
            $options,
            new TranslationPackageRequestAuthenticator($options, new MockClock('2025-01-01 UTC')),
        );

        $result = $transport->upload('archive');

        self::assertSame(1, $result->filesCreated);
        self::assertSame(4, $result->keysUpdated);
    }

    public function test_it_downloads_without_basic_auth_when_credentials_are_omitted(): void
    {
        $options = $this->options();
        $client = new MockHttpClient(function (string $method, string $url, array $requestOptions): MockResponse {
            self::assertSame('GET', $method);
            self::assertSame('https://example.test/api/translations/download', $url);
            self::assertArrayNotHasKey('auth_basic', $requestOptions);

            return new MockResponse('zip-content', ['http_code' => 200, 'response_headers' => ['content-type: application/zip']]);
        });
        $transport = new TranslationPackageHttpClient(
            $client,
            $options,
            new TranslationPackageRequestAuthenticator($options, new MockClock('2025-01-01 UTC')),
        );

        self::assertSame('zip-content', $transport->download());
    }

    private function options(?string $username = null, ?string $password = null): TranslationSynchronizationOptions
    {
        return new TranslationSynchronizationOptions(
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
            basicAuthUsername: $username,
            basicAuthPassword: $password,
        );
    }
}
