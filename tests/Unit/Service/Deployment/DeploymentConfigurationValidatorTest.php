<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Deployment;

use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationException;
use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationValidator;
use Lexio\AdminBundle\Service\Deployment\DeploymentOptions;
use Lexio\AdminBundle\Service\Deployment\DeploymentTranslationKeys;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeploymentConfigurationValidatorTest extends TestCase
{
    public function test_disabled_deployment_is_rejected_before_other_values_are_checked(): void
    {
        $this->expectException(DeploymentConfigurationException::class);
        $this->expectExceptionMessage(DeploymentTranslationKeys::DISABLED);

        (new DeploymentConfigurationValidator())->validate(new DeploymentOptions(
            enabled: false,
            host: null,
            user: null,
            port: 22,
            remotePath: null,
            deployScript: 'scripts/dev_next_deploy.sh',
            identityFile: null,
            timeout: null,
        ));
    }

    public function test_required_values_are_rejected_when_deployment_is_enabled(): void
    {
        $this->expectException(DeploymentConfigurationException::class);
        $this->expectExceptionMessage(DeploymentTranslationKeys::REQUIRED_VALUE);

        (new DeploymentConfigurationValidator())->validate(new DeploymentOptions(
            enabled: true,
            host: null,
            user: 'deployer',
            port: 22,
            remotePath: '/srv/app',
            deployScript: 'scripts/deploy.sh',
            identityFile: null,
            timeout: null,
        ));
    }

    public function test_shell_significant_remote_paths_are_allowed_for_safe_quoting(): void
    {
        (new DeploymentConfigurationValidator())->validate(new DeploymentOptions(
            enabled: true,
            host: 'deploy.example.com',
            user: 'deployer',
            port: 22,
            remotePath: '/srv/my application',
            deployScript: "scripts/deploy's.sh",
            identityFile: 'C:\\Keys\\deploy key',
            timeout: 300,
        ));

        self::assertTrue(true);
    }

    #[DataProvider('invalidConfigurationProvider')]
    public function test_invalid_configuration_values_are_rejected(DeploymentOptions $options): void
    {
        $this->expectException(DeploymentConfigurationException::class);
        $this->expectExceptionMessage(DeploymentTranslationKeys::INVALID_VALUE);

        (new DeploymentConfigurationValidator())->validate($options);
    }

    /** @return iterable<string, array{DeploymentOptions}> */
    public static function invalidConfigurationProvider(): iterable
    {
        yield 'host contains whitespace' => [new DeploymentOptions(
            true,
            'deploy example.com',
            'deployer',
            22,
            '/srv/app',
            'scripts/deploy.sh',
            null,
            null,
        )];
        yield 'username begins with an option marker' => [new DeploymentOptions(
            true,
            'deploy.example.com',
            '-F/tmp/ssh-config',
            22,
            '/srv/app',
            'scripts/deploy.sh',
            null,
            null,
        )];
        yield 'identity file begins with an option marker' => [new DeploymentOptions(
            true,
            'deploy.example.com',
            'deployer',
            22,
            '/srv/app',
            'scripts/deploy.sh',
            '-oProxyCommand=unsafe',
            null,
        )];
        yield 'remote path contains traversal' => [new DeploymentOptions(
            true,
            'deploy.example.com',
            'deployer',
            22,
            '/srv/../app',
            'scripts/deploy.sh',
            null,
            null,
        )];
        yield 'port is outside the SSH range' => [new DeploymentOptions(
            true,
            'deploy.example.com',
            'deployer',
            65536,
            '/srv/app',
            'scripts/deploy.sh',
            null,
            null,
        )];
        yield 'timeout is not positive' => [new DeploymentOptions(
            true,
            'deploy.example.com',
            'deployer',
            22,
            '/srv/app',
            'scripts/deploy.sh',
            null,
            0,
        )];
    }
}
