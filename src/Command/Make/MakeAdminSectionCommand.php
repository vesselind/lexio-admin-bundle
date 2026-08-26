<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Command\Make;

use Lexio\AdminBundle\Contract\AutoTranslator\AutoTranslatorInterface;
use RuntimeException;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;


/**
 * Code-generator that scaffolds a full admin CRUD section for a given entity:
 *   - Filter DTO
 *   - Admin controller
 *   - Repository filtered() method injection
 *   - Translation keys
 *   - Foundry factory (if available)
 *   - Integration test skeleton
 *
 * Requires `symfony/maker-bundle` to be installed.
 */
#[AsCommand(
    name: 'make:admin-crud',
    description: 'Creates a new admin CRUD controller for a given entity class name.',
)]
class MakeAdminSectionCommand extends Command
{
    public function __construct(
        private readonly Environment              $twig,
        #[Autowire('%kernel.project_dir%')]
        private readonly string                   $projectDir,
        #[Autowire('%lexio_admin.default_locale')]
        private readonly string                   $defaultLocale,
        private readonly ?AutoTranslatorInterface $translator = null,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question('What is the entity short class name? (e.g. "Blog") ');
        $question->setValidator(static function (?string $answer): string {
            if (empty($answer)) {
                throw new RuntimeException('You must provide a class name.');
            }

            return $answer;
        });
        $question->setMaxAttempts(1);

        $shortClassName = $helper->ask($input, $output, $question);
        $snakeCaseFileName = Str::asSnakeCase($shortClassName);
        $kebabCaseFileName = $this->toKebabCase($shortClassName);

        $this->getMetadata($shortClassName);

        $filter = $this->projectDir . $this->createFiltersObject($shortClassName, $snakeCaseFileName, $kebabCaseFileName);
        $controller = $this->projectDir . $this->createController($shortClassName, $snakeCaseFileName, $kebabCaseFileName);

        $repoResult = $this->injectRepositoryFilteredMethod($shortClassName, $snakeCaseFileName, $io);
        $translationResults = $this->createTranslationKeys($shortClassName, $snakeCaseFileName, $io);
        $factoryResult = $this->createFactory($shortClassName, $io, $output);
        $testResult = $this->createTest($shortClassName, $snakeCaseFileName, $kebabCaseFileName, $io);

        $io->success('Done!');
        $io->title('Files created / updated:');

        $listing = [$filter, $controller];

        if ($repoResult !== null) {
            $listing[] = $repoResult . ' (filtered() method injected)';
        }

        foreach ($translationResults as $translationResult) {
            $listing[] = $translationResult;
        }

        if ($factoryResult !== null) {
            $listing[] = $factoryResult . ' (factory created)';
        }

        if ($testResult !== null) {
            $listing[] = $testResult;
        }

        $io->listing($listing);

        return self::SUCCESS;
    }

    // ────────────────────────────────────────────────────────────────────────────

    private function getMetadata(string $shortClassName): array
    {
        $reflection = new \ReflectionClass('\\App\\Entity\\' . $shortClassName);
        $fieldsResult = [];

        foreach ($reflection->getProperties() as $property) {
            $reflectionType = $property->getType();
            $fieldType = $reflectionType instanceof \ReflectionNamedType ? $reflectionType->getName() : null;
            $fieldName = $property->getName();

            if ($fieldName === 'id') {
                continue;
            }

            $fieldsResult[$fieldName] = $fieldType;
        }

        return $fieldsResult;
    }

    private function createFiltersObject(string $shortClassName, string $snakeCaseClassName, string $kebabCaseClassName): string
    {
        $filePath = sprintf('%s/src/Filter/%sFilter.php', $this->projectDir, $shortClassName);
        $fields = [];

        foreach ($this->getMetadata($shortClassName) as $fieldName => $fieldType) {
            if ($fieldType === 'string' || $fieldType === 'int') {
                $fields[] = 'public ?' . $fieldType . ' $' . $fieldName . ' = null;';
            }
        }

        $source = $this->twig->render('@LexioAdmin/maker/filter.html.twig', [
            'shortClassName' => $shortClassName,
            'snakeCaseClassName' => $snakeCaseClassName,
            'kebabCaseClassName' => $kebabCaseClassName,
            'fields' => $fields,
        ]);

        file_put_contents($filePath, $source);

        return str_replace($this->projectDir, '', $filePath);
    }

    private function createController(string $shortClassName, string $snakeCaseClassName, string $kebabCaseClassName): string
    {
        $filePath = sprintf('%s/src/Controller/Admin/%sController.php', $this->projectDir, $shortClassName);
        $columnName = null;

        foreach ($this->getMetadata($shortClassName) as $fieldName => $fieldType) {
            if ($fieldType === 'string' || $fieldType === 'int') {
                $columnName = $fieldName;
                break;
            }
        }

        $source = $this->twig->render('@LexioAdmin/maker/crud_controller.html.twig', [
            'columnName' => $columnName,
            'shortClassName' => $shortClassName,
            'shortClassNameLowercase' => lcfirst($shortClassName),
            'snakeCaseClassName' => $snakeCaseClassName,
            'kebabCaseClassName' => $kebabCaseClassName,
        ]);

        file_put_contents($filePath, $source);

        return str_replace($this->projectDir, '', $filePath);
    }

    private function injectRepositoryFilteredMethod(string $shortClassName, string $snakeCaseClassName, SymfonyStyle $io): ?string
    {
        $repositoryPath = sprintf('%s/src/Repository/%sRepository.php', $this->projectDir, $shortClassName);

        if (!file_exists($repositoryPath)) {
            $io->warning(sprintf('Repository not found at %s — skipping filtered() injection.', $repositoryPath));

            return null;
        }

        $content = file_get_contents($repositoryPath);

        if (str_contains($content, 'public function filtered(')) {
            $io->note('filtered() already exists in the repository — skipping.');

            return null;
        }

        $alias = $snakeCaseClassName;
        $filterClass = $shortClassName . 'Filter';

        $conditionLines = [];
        foreach ($this->getMetadata($shortClassName) as $fieldName => $fieldType) {
            if ($fieldType === 'string') {
                $conditionLines[] = <<<PHP
        if (\$filter->{$fieldName}) {
            \$builder->andWhere('{$alias}.{$fieldName} LIKE :{$fieldName}')
                ->setParameter('{$fieldName}', '%' . \$filter->{$fieldName} . '%');
        }
PHP;
            } elseif ($fieldType === 'int') {
                $conditionLines[] = <<<PHP
        if (\$filter->{$fieldName} !== null) {
            \$builder->andWhere('{$alias}.{$fieldName} = :{$fieldName}')
                ->setParameter('{$fieldName}', \$filter->{$fieldName});
        }
PHP;
            }
        }

        $conditionsBlock = !empty($conditionLines) ? "\n" . implode("\n\n", $conditionLines) . "\n" : '';

        $method = <<<PHP

    public function filtered({$filterClass} \$filter): QueryBuilder
    {
        \$builder = \$this->createQueryBuilder('{$alias}')
            ->orderBy('{$alias}.id', 'DESC');
{$conditionsBlock}
        return \$builder;
    }

PHP;

        if (!str_contains($content, 'use Doctrine\ORM\QueryBuilder;')) {
            $content = str_replace(
                'use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;',
                "use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;\nuse Doctrine\ORM\QueryBuilder;",
                $content
            );
        }

        $filterImport = 'use App\\Filter\\' . $filterClass . ';';
        if (!str_contains($content, $filterImport)) {
            preg_match_all('/^use [^;]+;$/m', $content, $matches, PREG_OFFSET_CAPTURE);
            if (!empty($matches[0])) {
                $last = end($matches[0]);
                $insertAt = $last[1] + \strlen($last[0]);
                $content = substr($content, 0, $insertAt) . "\n" . $filterImport . substr($content, $insertAt);
            }
        }

        $lastBrace = strrpos($content, '}');
        $content = substr($content, 0, $lastBrace) . $method . "}\n";

        file_put_contents($repositoryPath, $content);

        return str_replace($this->projectDir, '', $repositoryPath);
    }

    private function createTranslationKeys(string $shortClassName, string $snakeCaseClassName, SymfonyStyle $io): array
    {
        $translationsDir = $this->projectDir . '/translations';
        $locales = [];

        foreach (['yaml', 'yml'] as $ext) {
            foreach (glob($translationsDir . '/form.*.' . $ext) ?: [] as $file) {
                if (preg_match('/form\.([^.]+)\.' . $ext . '$/', $file, $matches)) {
                    if (!\in_array($matches[1], $locales, true)) {
                        $locales[] = $matches[1];
                    }
                }
            }
        }

        if (empty($locales)) {
            $io->warning('No translation locale files found — skipping translation key generation.');

            return [];
        }

        $metadata = $this->getMetadata($shortClassName);
        $filterFields = array_filter($metadata, static fn(?string $t) => $t === 'string' || $t === 'int');

        $routes = [
            "admin.{$snakeCaseClassName}.index",
            "admin.{$snakeCaseClassName}.create",
            "admin.{$snakeCaseClassName}.update",
            "admin.{$snakeCaseClassName}.delete",
            "admin.{$snakeCaseClassName}.bulk_delete",
        ];

        $updatedFiles = [];

        foreach ($locales as $locale) {
            $formFile = $this->findTranslationFile($translationsDir, 'form', $locale);
            if ($formFile !== null) {
                $formContent = file_get_contents($formFile);
                $formKeysAdded = [];

                foreach (array_keys($filterFields) as $fieldName) {
                    $snakeField = Str::asSnakeCase($fieldName);

                    $labelKey = "label.{$snakeField}";
                    if (!$this->translationKeyExists($formContent, $labelKey)) {
                        $value = $this->localizeValue($this->humanizeField($snakeField), $locale, $labelKey);
                        $formContent .= "{$labelKey}: '{$value}'\n";
                        $formKeysAdded[] = $labelKey;
                    }

                    $placeholderKey = "placeholder.{$snakeField}";
                    if (!$this->translationKeyExists($formContent, $placeholderKey)) {
                        $value = $this->localizeValue($this->humanizeField($snakeField) . '...', $locale, $placeholderKey);
                        $formContent .= "{$placeholderKey}: '{$value}'\n";
                        $formKeysAdded[] = $placeholderKey;
                    }
                }

                if (!empty($formKeysAdded)) {
                    file_put_contents($formFile, $formContent);
                    $updatedFiles[] = str_replace($this->projectDir, '', $formFile)
                        . ' (+' . \count($formKeysAdded) . ' keys: ' . implode(', ', $formKeysAdded) . ')';
                }
            }

            $adminFile = $this->findTranslationFile($translationsDir, 'admin', $locale);
            if ($adminFile !== null) {
                $adminContent = file_get_contents($adminFile);
                $adminKeysAdded = [];

                foreach (array_keys($filterFields) as $fieldName) {
                    $snakeField = Str::asSnakeCase($fieldName);
                    $columnKey = "column.{$snakeField}";
                    if (!$this->translationKeyExists($adminContent, $columnKey)) {
                        $value = $this->localizeValue($this->humanizeField($snakeField), $locale, $columnKey);
                        $adminContent .= "{$columnKey}: '{$value}'\n";
                        $adminKeysAdded[] = $columnKey;
                    }
                }

                foreach ($routes as $routeKey) {
                    if (!$this->translationKeyExists($adminContent, $routeKey)) {
                        $value = $this->localizeValue($this->humanizeRoute($routeKey, $shortClassName), $locale, $routeKey);
                        $adminContent .= "{$routeKey}: '{$value}'\n";
                        $adminKeysAdded[] = $routeKey;
                    }
                }

                if (!empty($adminKeysAdded)) {
                    file_put_contents($adminFile, $adminContent);
                    $updatedFiles[] = str_replace($this->projectDir, '', $adminFile)
                        . ' (+' . \count($adminKeysAdded) . ' keys: ' . implode(', ', $adminKeysAdded) . ')';
                }
            }
        }

        return $updatedFiles;
    }

    private function findTranslationFile(string $dir, string $domain, string $locale): ?string
    {
        foreach (['yaml', 'yml'] as $ext) {
            $path = "{$dir}/{$domain}.{$locale}.{$ext}";
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function translationKeyExists(string $content, string $key): bool
    {
        return (bool)preg_match('/^' . preg_quote($key, '/') . '\s*:/m', $content);
    }

    private function localizeValue(string $englishValue, string $locale, string $fallbackKey): string
    {
        if ($locale === 'en') {
            return $englishValue;
        }

        if ($locale === $this->defaultLocale && $this->translator !== null && $this->translator->isActivated()) {
            try {
                return $this->translator->translate($englishValue, 'en', strtoupper($locale));
            } catch (\Throwable) {
                // fall through
            }
        }

        return "__{$fallbackKey}";
    }

    private function humanizeField(string $snakeCaseStr): string
    {
        return ucwords(str_replace('_', ' ', $snakeCaseStr));
    }

    private function humanizeRoute(string $routeKey, string $shortClassName): string
    {
        $action = substr($routeKey, strrpos($routeKey, '.') + 1);

        return match ($action) {
            'index' => $shortClassName . ' List',
            'create' => 'Create ' . $shortClassName,
            'update' => 'Edit ' . $shortClassName,
            'delete' => 'Delete ' . $shortClassName,
            'bulk_delete' => 'Bulk Delete ' . $shortClassName,
            default => ucwords(str_replace('_', ' ', $action)) . ' ' . $shortClassName,
        };
    }

    private function createFactory(string $shortClassName, SymfonyStyle $io, OutputInterface $output): ?string
    {
        $factoryPath = sprintf('%s/src/Factory/%sFactory.php', $this->projectDir, $shortClassName);

        if (file_exists($factoryPath)) {
            $io->note(sprintf('%sFactory already exists — skipping.', $shortClassName));

            return null;
        }

        try {
            $makeFactory = $this->getApplication()->find('make:factory');
            $makeInput = new ArrayInput(['class' => 'App\\Entity\\' . $shortClassName]);
            $makeInput->setInteractive(false);
            $makeFactory->run($makeInput, $output);
        } catch (\Throwable $e) {
            $io->warning(sprintf('make:factory failed: %s', $e->getMessage()));

            return null;
        }

        return file_exists($factoryPath) ? str_replace($this->projectDir, '', $factoryPath) : null;
    }

    private function createTest(string $shortClassName, string $snakeCaseClassName, string $kebabCaseClassName, SymfonyStyle $io): ?string
    {
        $testPath = sprintf('%s/tests/Web/Admin/%sTest.php', $this->projectDir, $shortClassName);

        if (file_exists($testPath)) {
            $io->note(sprintf('%sTest.php already exists — skipping.', $shortClassName));

            return null;
        }

        $filterFields = [];
        foreach ($this->getMetadata($shortClassName) as $fieldName => $fieldType) {
            if ($fieldType === 'string' || $fieldType === 'int') {
                $filterFields[] = [
                    'name' => $fieldName,
                    'getter' => 'get' . ucfirst($fieldName),
                ];
            }
        }

        $source = $this->twig->render('@LexioAdmin/maker/admin_test.html.twig', [
            'shortClassName' => $shortClassName,
            'snakeCaseClassName' => $snakeCaseClassName,
            'kebabCaseClassName' => $kebabCaseClassName,
            'filterFields' => $filterFields,
            'firstField' => $filterFields[0] ?? null,
        ]);

        file_put_contents($testPath, $source);

        return str_replace($this->projectDir, '', $testPath);
    }

    private function toKebabCase(string $string): string
    {
        $string = preg_replace('/[\s.]+/', '_', $string);
        $string = preg_replace('/[^0-9a-zA-Z_\-]/', '-', $string);
        $string = strtolower(preg_replace('/[A-Z]+/', '-\0', $string));
        $string = trim($string, '-_');

        return preg_replace('/[_\-][_\-]+/', '-', $string);
    }
}

