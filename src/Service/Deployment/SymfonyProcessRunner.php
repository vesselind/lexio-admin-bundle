<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Translation\TranslatableMessage;

final readonly class SymfonyProcessRunner implements ProcessRunnerInterface
{
    private const string TRANSLATION_DOMAIN = 'LexioAdminBundle';

    /**
     * @param list<string> $command
     * @param callable('out'|'err', string): void $output
     */
    public function run(array $command, callable $output, ?int $timeout): ProcessResult
    {
        $process = new Process($command);
        $process->setTimeout($timeout);

        try {
            $exitCode = $process->run($output);
        } catch (ProcessTimedOutException $exception) {
            return new ProcessResult(false, 124, true);
        } catch (ProcessStartFailedException $exception) {
            throw new DeploymentExecutionException(new TranslatableMessage(
                DeploymentTranslationKeys::PROCESS_START_FAILED,
                domain: self::TRANSLATION_DOMAIN,
            ), $exception);
        } catch (\Throwable $exception) {
            throw new DeploymentExecutionException(new TranslatableMessage(
                DeploymentTranslationKeys::PROCESS_FAILED,
                domain: self::TRANSLATION_DOMAIN,
            ), $exception);
        }

        return new ProcessResult(0 === $exitCode, $exitCode, false);
    }
}
