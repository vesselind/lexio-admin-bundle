<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command;

use Lexio\AdminBundle\Api\Service\ApiKeyIssuer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ApiKeyIssueCommand extends Command
{
    public function __construct(
        private readonly ApiKeyIssuer $issuer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Issue (or rotate) the API key of an existing user and display the raw secret once.');
        $this->addArgument('email', InputArgument::REQUIRED, 'Identifier of the user the key belongs to (email by default).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $rawKey = $this->issuer->issue($email);

        if ($rawKey === null) {
            $io->error('The user was not found.');

            return Command::FAILURE;
        }

        $io->success('API key issued. Save the following secret securely; it cannot be retrieved again. The previous key is now invalid.');
        $io->writeln(sprintf('API key for %s: %s', $email, $rawKey));
        $io->note('Send this key on every request as: Authorization: Bearer <api-key>');

        return Command::SUCCESS;
    }
}
