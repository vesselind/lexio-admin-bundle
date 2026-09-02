<?php

namespace Lexio\AdminBundle\Command\Seeder;

use Lexio\AdminBundle\Entity\Seed as SeederEntity;
use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Contract\Seeder\SeedersRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'seeder:tasks:list',
    description: 'List all data loader (seeders) tasks with their execution status.',
)]
class ListTasksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeedersRegistryInterface $seedersRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Data Loader Tasks Status');

        // Get registered tasks from the centralized DataLoader service
        $registeredTasks = $this->seedersRegistry->getRegistry();

        // Get all executed tasks from database
        $executedTasks = $this->entityManager
            ->getRepository(SeederEntity::class)
            ->findAll();

        // Create a map of executed tasks for quick lookup
        $executedTasksMap = [];
        foreach ($executedTasks as $task) {
            $executedTasksMap[$task->getName()] = [
                'id' => $task->getId(),
                'created_at' => $task->getCreatedAt(),
                'updated_at' => $task->getUpdatedAt(),
            ];
        }

        // Prepare table data
        $tableRows = [];
        foreach ($registeredTasks as $index => $taskClass) {
            $taskName = $this->getShortTaskName($taskClass);
            $isExecuted = isset($executedTasksMap[$taskClass]);

            $tableRows[] = [
                $index + 1,
                $taskName,
                $taskClass,
                $isExecuted ? '✓ Yes' : '✗ No',
                $isExecuted && isset($executedTasksMap[$taskClass]['created_at']) ? $executedTasksMap[$taskClass]['created_at']->format('Y-m-d H:i:s') : '-',
            ];
        }

        // Create and render the table
        $table = new Table($output);
        $table
            ->setHeaders(['#', 'Task Name', 'Class', 'Executed', 'Executed At'])
            ->setRows($tableRows);

        $table->render();

        // Summary
        $totalTasks = count($registeredTasks);
        $executedCount = count($executedTasks);
        $pendingCount = $totalTasks - $executedCount;

        $io->newLine();
        $io->writeln(sprintf('Total Tasks: <info>%d</info>', $totalTasks));
        $io->writeln(sprintf('Executed: <fg=green>%d</>', $executedCount));
        $io->writeln(sprintf('Pending: <fg=yellow>%d</>', $pendingCount));

        return Command::SUCCESS;
    }

    private function getShortTaskName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}

