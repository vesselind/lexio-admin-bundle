<?php

namespace Lexio\AdminBundle\Command\Seeder;

use Lexio\AdminBundle\Entity\Seed as SeedEntity;
use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Contract\Seeder\SeedersRegistryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'seeder:task:rerun',
    description: 'Re-execute a specific data loader (seeder) task.',
)]
class RerunTaskCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SeedersRegistryInterface $seedersRegistry,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'task',
            InputArgument::OPTIONAL,
            'The task class name (short name or FQCN) to re-execute'
        );
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $taskArgument = $input->getArgument('task');

        // Get registered tasks from the centralized DataLoader service
        $registeredTasks = $this->seedersRegistry->getRegistry();

        if (empty($registeredTasks)) {
            $io->error('No tasks are registered in the SeedersRegistry.');
            return Command::FAILURE;
        }

        // If no argument provided, show interactive selection
        if (!$taskArgument) {
            $selectedTaskClass = $this->selectTaskInteractively($input, $output, $io, $registeredTasks);
            if (!$selectedTaskClass) {
                return Command::FAILURE;
            }
        } else {
            // Find task by argument (supports both short name and FQCN)
            $selectedTaskClass = $this->findTaskByName($taskArgument, $registeredTasks);
            if (!$selectedTaskClass) {
                $io->error(sprintf('Task "%s" not found in registered tasks.', $taskArgument));
                $io->note('Use "php bin/console seeder:tasks:list" to see all available tasks.');
                return Command::FAILURE;
            }
        }

        // Confirm before re-execution
        $shortName = $this->getShortTaskName($selectedTaskClass);
        if (!$io->confirm(sprintf('Are you sure you want to re-execute task "%s"?', $shortName), false)) {
            $io->warning('Task execution cancelled.');
            return Command::SUCCESS;
        }

        // Execute the task
        return $this->executeTask($io, $selectedTaskClass);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param SymfonyStyle $io
     * @param array<int, class-string> $registeredTasks
     * @return string|null
     */
    private function selectTaskInteractively(
        InputInterface $input,
        OutputInterface $output,
        SymfonyStyle $io,
        array $registeredTasks
    ): ?string {
        $io->title('Select a Task to Re-execute');

        // Get executed tasks info
        $executedTasks = $this->entityManager
            ->getRepository(SeedEntity::class)
            ->findAll();

        $executedTasksMap = [];
        foreach ($executedTasks as $task) {
            $executedTasksMap[$task->getName()] = [
                'executed_at' => $task->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'Unknown',
            ];
        }

        // Create numbered list of tasks
        $choices = [];
        $taskMap = [];
        foreach ($registeredTasks as $index => $taskClass) {
            $shortName = $this->getShortTaskName($taskClass);
            $status = isset($executedTasksMap[$taskClass]) ? '✓ Executed' : '✗ Not executed';
            $label = sprintf('%d. %s [%s]', $index + 1, $shortName, $status);

            $choices[] = $label;
            $taskMap[$label] = $taskClass;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select the task number to re-execute:',
            $choices
        );
        $question->setErrorMessage('Task %s is invalid.');


        $selectedLabel = $helper->ask($input, $output, $question);

        return $taskMap[$selectedLabel] ?? null;
    }

    /**
     * @param class-string $taskName
     * @param array<int, class-string> $registeredTasks
     * @return class-string|null
     */
    private function findTaskByName(string $taskName, array $registeredTasks): ?string
    {
        // Check if it's already a FQCN and exists
        if (in_array($taskName, $registeredTasks, true)) {
            return $taskName;
        }

        // Try to find by short name
        return array_find($registeredTasks, fn($taskClass) => $this->getShortTaskName($taskClass) === $taskName);
    }

    /**
     * @param SymfonyStyle $io
     * @param class-string $taskClass
     * @return int
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    private function executeTask(SymfonyStyle $io, string $taskClass): int
    {
        $shortName = $this->getShortTaskName($taskClass);

        $io->section(sprintf('Re-executing task: %s', $shortName));
        $io->writeln(sprintf('Class: <info>%s</info>', $taskClass));

        try {
            // Check if task was previously executed
            $existingRecord = $this->entityManager
                ->getRepository(SeedEntity::class)
                ->findOneBy(['name' => $taskClass]);

            if ($existingRecord) {
                $io->note(sprintf(
                    'Task was previously executed at: %s',
                    $existingRecord->getCreatedAt()?->format('Y-m-d H:i:s') ?? 'Unknown'
                ));

                // Remove the existing record to allow re-execution
                $this->entityManager->remove($existingRecord);
                $this->entityManager->flush();
                $io->writeln('<comment>Removed previous execution record.</comment>');
            }

            // Dispatch the task
            $io->writeln('Dispatching task...');
            $this->messageBus->dispatch(new $taskClass());

            // Create a new execution record
            $loaderEntity = new SeedEntity();
            $loaderEntity->setName($taskClass);

            $this->entityManager->persist($loaderEntity);
            $this->entityManager->flush();

            $io->success(sprintf('Task "%s" was re-executed successfully!', $shortName));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                sprintf('Failed to execute task "%s"', $shortName),
                'Error: ' . $e->getMessage(),
            ]);
            return Command::FAILURE;
        }
    }

    private function getShortTaskName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}

