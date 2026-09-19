<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command\Translations;

use Lexio\AdminBundle\Service\Translation\TranslationScanException;
use Lexio\AdminBundle\Service\Translation\TranslationScanInputException;
use Lexio\AdminBundle\Service\Translation\TranslationScanner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ScanTranslationsCommand extends Command
{
    public function __construct(
        private readonly TranslationScanner $scanner,
    ) {
        parent::__construct('translations:scan');
        $this->setDescription('Find potentially untranslated text and placeholder translation values.');
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Twig file or directory, or a bundle-managed YAML translation file.',
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Output format: text or json.',
                'text',
            )
            ->addOption(
                'fail-on-findings',
                null,
                InputOption::VALUE_NONE,
                'Return a non-zero exit code when untranslated strings are found.',
            )
            ->addOption(
                'min-length',
                null,
                InputOption::VALUE_REQUIRED,
                'Minimum length of Twig text candidates.',
                '2',
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Template directory name to exclude; can be provided multiple times.',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = (string) $input->getOption('format');
        if (!in_array($format, ['text', 'json'], true)) {
            $io->error('The --format option must be either "text" or "json".');

            return self::INVALID;
        }

        $minLength = filter_var((string) $input->getOption('min-length'), FILTER_VALIDATE_INT);
        if (!is_int($minLength) || $minLength < 1) {
            $io->error('The --min-length option must be an integer greater than or equal to 1.');

            return self::INVALID;
        }

        $path = $input->getArgument('path');
        if (null !== $path && !is_string($path)) {
            $io->error('The path argument must be a string.');

            return self::INVALID;
        }

        $exclude = $input->getOption('exclude');
        $excludedDirectories = is_array($exclude)
            ? array_values(array_map(static fn (mixed $directory): string => (string) $directory, $exclude))
            : [];

        try {
            $findings = $this->scanner->scan($path, $minLength, $excludedDirectories);
        } catch (TranslationScanInputException $exception) {
            $io->error($exception->getMessage());

            return self::INVALID;
        } catch (TranslationScanException $exception) {
            $io->error($exception->getMessage());

            return self::FAILURE;
        }

        if ('json' === $format) {
            try {
                $io->writeln(json_encode(
                    $findings,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ));
            } catch (\JsonException) {
                $io->error('The findings could not be encoded as JSON.');

                return self::FAILURE;
            }
        } else {
            $this->renderTextOutput($io, $findings);
        }

        return $findings !== [] && $input->getOption('fail-on-findings')
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param list<array{
     *     file: string,
     *     line: int,
     *     type: 'text'|'attribute'|'placeholder',
     *     value: string,
     *     attribute?: string,
     *     key?: string
     * }> $findings
     */
    private function renderTextOutput(SymfonyStyle $io, array $findings): void
    {
        if ([] === $findings) {
            $io->success('No potentially untranslated strings found.');

            return;
        }

        $currentFile = null;
        foreach ($findings as $finding) {
            if ($currentFile !== $finding['file']) {
                $currentFile = $finding['file'];
                $io->section($currentFile);
            }

            $type = match ($finding['type']) {
                'attribute' => sprintf('attribute: %s', $finding['attribute'] ?? 'unknown'),
                'placeholder' => sprintf('placeholder: %s', $finding['key'] ?? 'unknown'),
                default => 'text',
            };
            $io->writeln(sprintf(
                '  <fg=yellow>Line %-5d</> <fg=gray>[%s]</> %s',
                $finding['line'],
                $type,
                $finding['value'],
            ));
        }

        $io->newLine();
        $fileCount = count(array_unique(array_column($findings, 'file')));
        $io->warning(sprintf(
            'Found %d potentially untranslated string%s in %d file%s.',
            count($findings),
            count($findings) === 1 ? '' : 's',
            $fileCount,
            $fileCount === 1 ? '' : 's',
        ));
    }
}
