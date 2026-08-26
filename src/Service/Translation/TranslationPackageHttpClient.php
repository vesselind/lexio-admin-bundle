<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class TranslationPackageHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private TranslationSynchronizationOptions $options,
        private TranslationPackageRequestAuthenticator $authenticator,
    ) {
    }

    public function upload(string $archive): TranslationPackageMergeResult
    {
        $path = $this->options->getUploadPath();
        $response = $this->request('POST', $path, $archive, ['Content-Type' => 'application/zip']);
        $content = $this->successfulContent($response, 'The deployed application rejected the translation package.');

        try {
            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TranslationSynchronizationException('The deployed application returned an invalid response.', previous: $exception);
        }

        if (!is_array($data)) {
            throw new TranslationSynchronizationException('The deployed application returned an invalid response.');
        }

        $values = [];
        foreach (['files_created', 'files_updated', 'keys_inserted', 'keys_updated', 'keys_unchanged'] as $key) {
            $value = $data[$key] ?? null;
            if (!is_int($value) || $value < 0) {
                throw new TranslationSynchronizationException('The deployed application returned an invalid response.');
            }
            $values[$key] = $value;
        }

        return new TranslationPackageMergeResult(
            filesCreated: $values['files_created'],
            filesUpdated: $values['files_updated'],
            keysInserted: $values['keys_inserted'],
            keysUpdated: $values['keys_updated'],
            keysUnchanged: $values['keys_unchanged'],
        );
    }

    public function download(): string
    {
        $response = $this->request('GET', $this->options->getDownloadPath(), '');

        return $this->successfulContent($response, 'The deployed application could not provide the translation package.');
    }

    /** @param array<string, string> $headers */
    private function request(string $method, string $path, string $body, array $headers = []): ResponseInterface
    {
        $baseUrl = $this->options->getDeployedAppUrl();
        if (null === $baseUrl) {
            throw new TranslationSynchronizationException('The deployed application URL is not configured.');
        }

        $requestOptions = [
            'headers' => array_merge($headers, $this->authenticator->createHeaders($method, $path, $body)),
            'timeout' => $this->options->getTimeout(),
            'max_duration' => $this->options->getTimeout(),
            'max_redirects' => 0,
        ];
        if ('POST' === $method) {
            $requestOptions['body'] = $body;
        }

        $username = $this->options->getBasicAuthUsername();
        $password = $this->options->getBasicAuthPassword();
        if (null !== $username && null !== $password) {
            $requestOptions['auth_basic'] = [$username, $password];
        }

        try {
            return $this->httpClient->request($method, $baseUrl . $path, $requestOptions);
        } catch (TransportExceptionInterface $exception) {
            throw new TranslationSynchronizationException('The deployed application could not be reached.', previous: $exception);
        }
    }

    private function successfulContent(ResponseInterface $response, string $failureMessage): string
    {
        try {
            if (200 !== $response->getStatusCode()) {
                throw new TranslationSynchronizationException($failureMessage);
            }

            $headers = $response->getHeaders(false);
            $contentLength = $headers['content-length'][0] ?? null;
            if (is_string($contentLength) && ctype_digit($contentLength) && (int) $contentLength > $this->options->getMaxPackageBytes()) {
                throw new TranslationSynchronizationException('The deployed application response is too large.');
            }

            $content = '';
            foreach ($this->httpClient->stream($response, $this->options->getTimeout()) as $chunk) {
                if ($chunk->isTimeout()) {
                    throw new TranslationSynchronizationException('The deployed application response timed out.');
                }

                $content .= $chunk->getContent();
                if (strlen($content) > $this->options->getMaxPackageBytes()) {
                    throw new TranslationSynchronizationException('The deployed application response is too large.');
                }
            }
        } catch (TransportExceptionInterface $exception) {
            throw new TranslationSynchronizationException('The deployed application response could not be read.', previous: $exception);
        }

        return $content;
    }
}
