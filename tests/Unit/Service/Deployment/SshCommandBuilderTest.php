<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Service\Deployment;

use Lexio\AdminBundle\Service\Deployment\DeploymentOptions;
use Lexio\AdminBundle\Service\Deployment\SshCommandBuilder;
use PHPUnit\Framework\TestCase;

final class SshCommandBuilderTest extends TestCase
{
    public function test_builds_separate_ssh_arguments_and_quotes_remote_paths(): void
    {
        $options = new DeploymentOptions(
            enabled: true,
            host: 'deploy.example.com',
            user: 'deployer',
            port: 2222,
            remotePath: '/srv/my application',
            deployScript: "scripts/deploy's.sh",
            identityFile: 'C:\\Keys\\deploy key',
            timeout: 300,
        );

        $command = (new SshCommandBuilder())->build($options);

        self::assertSame([
            'ssh',
            '-p',
            '2222',
            '-i',
            'C:\\Keys\\deploy key',
            'deployer@deploy.example.com',
            "cd '/srv/my application' && git pull && bash 'scripts/deploy'\\''s.sh'",
        ], $command);
    }

    public function test_omits_identity_argument_when_no_identity_file_is_configured(): void
    {
        $command = (new SshCommandBuilder())->build(new DeploymentOptions(
            enabled: true,
            host: 'deploy.example.com',
            user: 'deployer',
            port: 22,
            remotePath: 'htdocs/site',
            deployScript: 'scripts/dev_next_deploy.sh',
            identityFile: null,
            timeout: null,
        ));

        self::assertSame('ssh', $command[0]);
        self::assertSame('-p', $command[1]);
        self::assertSame('22', $command[2]);
        self::assertSame('deployer@deploy.example.com', $command[3]);
        self::assertSame(
            "cd 'htdocs/site' && git pull && bash 'scripts/dev_next_deploy.sh'",
            $command[4],
        );
        self::assertNotContains('-i', $command);
    }

    public function test_requires_validated_connection_values(): void
    {
        $this->expectException(\LogicException::class);

        (new SshCommandBuilder())->build(new DeploymentOptions(
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
}
