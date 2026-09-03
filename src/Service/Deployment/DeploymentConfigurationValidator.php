<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

use Symfony\Component\Translation\TranslatableMessage;

final readonly class DeploymentConfigurationValidator
{
    private const string TRANSLATION_DOMAIN = 'LexioAdminBundle';

    public function validate(DeploymentOptions $options): void
    {
        if (!$options->enabled) {
            throw new DeploymentConfigurationException(new TranslatableMessage(
                DeploymentTranslationKeys::DISABLED,
                domain: self::TRANSLATION_DOMAIN,
            ));
        }

        $host = $this->requireValue('host', $options->host);
        $user = $this->requireValue('user', $options->user);
        $remotePath = $this->requireValue('remote_path', $options->remotePath);
        $this->validateHostOrUser('host', $host);
        $this->validateHostOrUser('user', $user);
        $this->validatePath('remote_path', $remotePath);
        $this->validatePath('deploy_script', $options->deployScript);

        if ($options->port < 1 || $options->port > 65535) {
            $this->throwInvalid('port');
        }

        if (null !== $options->identityFile) {
            $this->validateValue('identity_file', $options->identityFile);

            if (str_starts_with($options->identityFile, '-')) {
                $this->throwInvalid('identity_file');
            }
        }

        if (null !== $options->timeout && $options->timeout < 1) {
            $this->throwInvalid('timeout');
        }
    }

    private function requireValue(string $field, ?string $value): string
    {
        if (null === $value || '' === trim($value)) {
            throw new DeploymentConfigurationException(new TranslatableMessage(
                DeploymentTranslationKeys::REQUIRED_VALUE,
                ['field' => $field],
                self::TRANSLATION_DOMAIN,
            ));
        }

        return $value;
    }

    private function validateHostOrUser(string $field, string $value): void
    {
        $this->validateValue($field, $value);

        if (preg_match('/\s/', $value) === 1 || str_contains($value, '@') || str_starts_with($value, '-')) {
            $this->throwInvalid($field);
        }
    }

    private function validatePath(string $field, string $value): void
    {
        $this->validateValue($field, $value);

        if (str_starts_with($value, '-')) {
            $this->throwInvalid($field);
        }

        $segments = preg_split('#/+#', trim($value, '/'));
        if (false === $segments) {
            $this->throwInvalid($field);
        }

        foreach ($segments as $segment) {
            if ('.' === $segment || '..' === $segment) {
                $this->throwInvalid($field);
            }
        }
    }

    private function validateValue(string $field, string $value): void
    {
        if ('' === trim($value) || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            $this->throwInvalid($field);
        }
    }

    private function throwInvalid(string $field): never
    {
        throw new DeploymentConfigurationException(new TranslatableMessage(
            DeploymentTranslationKeys::INVALID_VALUE,
            ['field' => $field],
            self::TRANSLATION_DOMAIN,
        ));
    }
}
