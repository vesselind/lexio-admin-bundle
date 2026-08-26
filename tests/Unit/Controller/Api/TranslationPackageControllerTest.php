<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Controller\Api;

use Lexio\AdminBundle\Controller\Api\TranslationPackageController;
use Lexio\AdminBundle\Service\Translation\AtomicTranslationFileWriter;
use Lexio\AdminBundle\Service\Translation\FlatTranslationDocumentCodec;
use Lexio\AdminBundle\Service\Translation\TranslationPackageManager;
use Lexio\AdminBundle\Service\Translation\TranslationPackageRequestAuthenticator;
use Lexio\AdminBundle\Service\Translation\TranslationSynchronizationOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TranslationPackageControllerTest extends TestCase
{
    private string $directory;
    private TranslationSynchronizationOptions $options;
    private TranslationPackageRequestAuthenticator $authenticator;
    private TranslationPackageManager $manager;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lexio-api-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->directory, 0777, true));
        $this->options = new TranslationSynchronizationOptions(
            enabled: true,
            environment: 'test',
            appSecret: 'secret',
            deployedAppUrl: 'https://example.test',
            apiPath: '/api/translations',
            authSalt: 'salt',
            signatureTtl: 300,
            timeout: 30,
            maxPackageBytes: 1048576,
            maxFiles: 20,
            basicAuthUsername: null,
            basicAuthPassword: null,
        );
        $this->authenticator = new TranslationPackageRequestAuthenticator($this->options, new MockClock('2025-01-01 UTC'));
        $this->manager = new TranslationPackageManager(
            $this->directory,
            $this->options,
            new FlatTranslationDocumentCodec(),
            new AtomicTranslationFileWriter(),
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->directory);
    }

    public function test_upload_requires_a_valid_signature_before_modifying_translations(): void
    {
        $request = Request::create('/api/translations/upload', 'POST', [], [], [], [], 'invalid archive');

        $response = $this->controller()->upload($request, $this->authenticator, $this->manager);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame([], glob($this->directory . DIRECTORY_SEPARATOR . '*.yaml') ?: []);
        self::assertSame('application/problem+json', $response->headers->get('Content-Type'));
    }

    public function test_download_returns_a_signed_zip_package(): void
    {
        self::assertNotFalse(file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'admin.en.yaml', "label.title: Title\n"));
        $request = Request::create('/api/translations/download', 'GET');
        foreach ($this->authenticator->createHeaders('GET', '/api/translations/download', '') as $name => $value) {
            $request->headers->set($name, $value);
        }

        $response = $this->controller()->download($request, $this->authenticator, $this->manager);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('application/zip', $response->headers->get('Content-Type'));
        $content = $response->getContent();
        self::assertIsString($content);
        self::assertStringStartsWith('PK', $content);
    }

    private function controller(): TranslationPackageController
    {
        return new class extends TranslationPackageController {
        };
    }
}
