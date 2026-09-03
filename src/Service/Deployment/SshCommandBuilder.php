<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

final readonly class SshCommandBuilder
{
    /**
     * @return list<string>
     */
    public function build(DeploymentOptions $options): array
    {
        if (null === $options->host || null === $options->user || null === $options->remotePath) {
            throw new \LogicException('Deployment configuration must be validated before building an SSH command.');
        }

        $command = [
            'ssh',
            '-p',
            (string) $options->port,
        ];

        if (null !== $options->identityFile) {
            $command[] = '-i';
            $command[] = $options->identityFile;
        }

        $command[] = $options->user . '@' . $options->host;
        $command[] = sprintf(
            'cd %s && git pull && bash %s',
            $this->quoteRemotePath($options->remotePath),
            $this->quoteRemotePath($options->deployScript),
        );

        return $command;
    }

    private function quoteRemotePath(string $path): string
    {
        return "'" . str_replace("'", "'\\''", $path) . "'";
    }
}
