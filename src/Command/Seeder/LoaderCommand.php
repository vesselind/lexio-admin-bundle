<?php

namespace Lexio\AdminBundle\Command\Seeder;

use Lexio\AdminBundle\Seeder\SeedLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'seeder:load',
    description: 'Running data loaders/seeders.',
)]
class LoaderCommand extends Command
{
    public function __construct(private readonly SeedLoader $seedLoader)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->seedLoader->load($io);

        return self::SUCCESS;
    }
}
