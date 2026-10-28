<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command;

use Lexio\AdminBundle\Api\Service\ApiKeyIssuer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ApiKeyBackfillCommand extends Command
{
    public function __construct(
        private readonly ApiKeyIssuer $issuer,
    ) {
        parent::__construct();
        $this->setDescription('Generate API keys for existing users that do not have one yet.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->issuer->backfill();
        $io->success(sprintf('Generated %d missing API credentials. Existing keys were preserved.', $count));

        return Command::SUCCESS;
    }
}
