<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\DependencyInjection;

use Lexio\AdminBundle\LexioAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DeploymentConfigurationTest extends TestCase
{
    public function test_deployment_configuration_uses_safe_defaults(): void
    {
        $configuration = $this->getConfiguration();
        $processed = (new Processor())->processConfiguration($configuration, [[]]);

        self::assertFalse($processed['deployment']['enabled']);
        self::assertNull($processed['deployment']['host']);
        self::assertNull($processed['deployment']['user']);
        self::assertSame(22, $processed['deployment']['port']);
        self::assertNull($processed['deployment']['remote_path']);
        self::assertSame('scripts/dev_next_deploy.sh', $processed['deployment']['deploy_script']);
        self::assertNull($processed['deployment']['identity_file']);
        self::assertNull($processed['deployment']['timeout']);
    }

    public function test_deployment_configuration_accepts_host_overrides(): void
    {
        $configuration = $this->getConfiguration();
        $processed = (new Processor())->processConfiguration($configuration, [[
            'deployment' => [
                'enabled' => true,
                'host' => 'deploy.example.com',
                'user' => 'deployer',
                'port' => 2222,
                'remote_path' => '/srv/example app',
                'deploy_script' => 'scripts/deploy.sh',
                'identity_file' => 'C:\\Keys\\deploy key',
                'timeout' => 300,
            ],
        ]]);

        self::assertTrue($processed['deployment']['enabled']);
        self::assertSame('deploy.example.com', $processed['deployment']['host']);
        self::assertSame('deployer', $processed['deployment']['user']);
        self::assertSame(2222, $processed['deployment']['port']);
        self::assertSame('/srv/example app', $processed['deployment']['remote_path']);
        self::assertSame('scripts/deploy.sh', $processed['deployment']['deploy_script']);
        self::assertSame('C:\\Keys\\deploy key', $processed['deployment']['identity_file']);
        self::assertSame(300, $processed['deployment']['timeout']);
    }

    private function getConfiguration(): object
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();

        self::assertNotNull($extension);
        $configuration = $extension->getConfiguration([], $container);
        self::assertNotNull($configuration);

        return $configuration;
    }
}
