<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

final readonly class TranslationSynchronizationOptions
{
    private ?string $deployedAppUrl;
    private string $apiPath;
    private string $authSalt;
    private ?string $basicAuthUsername;
    private ?string $basicAuthPassword;

    public function __construct(
        private bool $enabled,
        private string $environment,
        #[\SensitiveParameter]
        private string $appSecret,
        ?string $deployedAppUrl,
        string $apiPath,
        ?string $authSalt,
        private int $signatureTtl,
        private int $timeout,
        private int $maxPackageBytes,
        private int $maxFiles,
        ?string $basicAuthUsername,
        #[\SensitiveParameter]
        ?string $basicAuthPassword,
    ) {
        $normalizedDeployedAppUrl = $this->normalizeNullable($deployedAppUrl);
        $this->apiPath = '/' . trim($apiPath, '/');
        $this->authSalt = trim($authSalt ?? '');
        $this->basicAuthUsername = $this->normalizeNullable($basicAuthUsername);
        $this->basicAuthPassword = $this->normalizeNullable($basicAuthPassword);

        if ($this->enabled && ('' === trim($this->appSecret) || '' === $this->authSalt)) {
            throw new \LogicException('Translation synchronization requires kernel.secret and a non-empty auth_salt.');
        }

        if ((null === $this->basicAuthUsername) !== (null === $this->basicAuthPassword)) {
            throw new \LogicException('Translation synchronization HTTP Basic Auth username and password must be configured together.');
        }

        if (1 !== preg_match('{^/(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+$}D', $this->apiPath)) {
            throw new \LogicException('The translation synchronization API path must be a non-empty path without a query or fragment.');
        }

        if (null !== $normalizedDeployedAppUrl) {
            $parts = parse_url($normalizedDeployedAppUrl);
            if (
                !is_array($parts)
                || !isset($parts['scheme'], $parts['host'])
                || !in_array($parts['scheme'], ['http', 'https'], true)
                || isset($parts['user'])
                || isset($parts['pass'])
                || isset($parts['query'])
                || isset($parts['fragment'])
            ) {
                throw new \LogicException('The deployed application URL must be an absolute HTTP or HTTPS URL.');
            }

            $normalizedDeployedAppUrl = rtrim($normalizedDeployedAppUrl, '/');
        }

        $this->deployedAppUrl = $normalizedDeployedAppUrl;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function areAdminActionsAllowed(): bool
    {
        return $this->enabled && 'prod' !== $this->environment;
    }

    public function getDeployedAppUrl(): ?string
    {
        return $this->deployedAppUrl;
    }

    public function getApiPath(): string
    {
        return $this->apiPath;
    }

    public function getUploadPath(): string
    {
        return $this->apiPath . '/upload';
    }

    public function getDownloadPath(): string
    {
        return $this->apiPath . '/download';
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function getAuthSalt(): string
    {
        return $this->authSalt;
    }

    public function getSignatureTtl(): int
    {
        return $this->signatureTtl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxPackageBytes(): int
    {
        return $this->maxPackageBytes;
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function getBasicAuthUsername(): ?string
    {
        return $this->basicAuthUsername;
    }

    public function getBasicAuthPassword(): ?string
    {
        return $this->basicAuthPassword;
    }

    private function normalizeNullable(?string $value): ?string
    {
        $value = trim($value ?? '');

        return '' === $value ? null : $value;
    }
}
