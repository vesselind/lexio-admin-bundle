<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command;

use Lexio\AdminBundle\Contract\Deployment\DeploymentRunnerInterface;
use Lexio\AdminBundle\Service\Deployment\DeploymentConfigurationException;
use Lexio\AdminBundle\Service\Deployment\DeploymentExecutionException;
use Lexio\AdminBundle\Service\Deployment\DeploymentTranslationKeys;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProdDeployCommand extends Command
{
    private const string TRANSLATION_DOMAIN = 'LexioAdminBundle';

    public function __construct(
        private readonly DeploymentRunnerInterface $runner,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct('prod:deploy');
        $this->setDescription($this->translate(DeploymentTranslationKeys::DESCRIPTION));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->translate(DeploymentTranslationKeys::TITLE));
        $io->text($this->translate(DeploymentTranslationKeys::EXECUTING));

        try {
            $result = $this->runner->deploy(
                static function (string $type, string $buffer) use ($io): void {
                    $io->write($buffer);
                },
            );
        } catch (DeploymentConfigurationException $exception) {
            $io->error($exception->getTranslatableMessage()->trans($this->translator));

            return self::FAILURE;
        } catch (DeploymentExecutionException $exception) {
            $io->error($exception->getTranslatableMessage()->trans($this->translator));

            return self::FAILURE;
        }

        if (!$result->successful) {
            $message = $result->timedOut
                ? $this->translate(DeploymentTranslationKeys::PROCESS_TIMEOUT)
                : $this->translate(DeploymentTranslationKeys::FAILED, ['exit_code' => $result->exitCode]);
            $io->error($message);

            return self::FAILURE;
        }

        $io->success($this->translate(DeploymentTranslationKeys::COMPLETED));

        return self::SUCCESS;
    }

    /** @param array<string, scalar> $parameters */
    private function translate(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, self::TRANSLATION_DOMAIN);
    }
}
