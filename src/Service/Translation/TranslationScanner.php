<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

final readonly class TranslationScanner
{
    /**
     * HTML attributes whose values are typically visible to users or exposed through accessibility interfaces.
     *
     * @var list<string>
     */
    private const TRANSLATABLE_ATTRIBUTES = [
        'alt',
        'title',
        'placeholder',
        'aria-label',
        'aria-description',
        'data-bs-original-title',
        'data-bs-title',
    ];

    public function __construct(
        private string $projectDirectory,
        private string $translationDirectory,
        private bool $enabled,
        private FlatTranslationDocumentCodec $codec,
    ) {
    }

    /**
     * @param list<string> $excludedDirectories
     *
     * @return list<array{
     *     file: string,
     *     line: int,
     *     type: 'text'|'attribute'|'placeholder',
     *     value: string,
     *     attribute?: string,
     *     key?: string
     * }>
     */
    public function scan(?string $path, int $minLength, array $excludedDirectories): array
    {
        $this->assertEnabled();

        if ($minLength < 1) {
            throw new TranslationScanInputException('The minimum length must be at least 1.');
        }

        $excludedDirectories = array_values(array_unique(array_filter(
            array_map(static fn (string $directory): string => trim($directory), $excludedDirectories),
            static fn (string $directory): bool => '' !== $directory,
        )));

        [$twigFiles, $translationFiles] = $this->resolveFiles($path, $excludedDirectories);

        $findings = [];
        foreach ($twigFiles as $file) {
            array_push($findings, ...$this->scanTwigFile($file, $minLength));
        }

        foreach ($translationFiles as $file) {
            array_push($findings, ...$this->scanTranslationFile($file));
        }

        usort(
            $findings,
            static fn (array $first, array $second): int =>
                [$first['file'], $first['line'], $first['type'], $first['value']]
                <=>
                [$second['file'], $second['line'], $second['type'], $second['value']],
        );

        return $findings;
    }

    /**
     * @param list<string> $excludedDirectories
     *
     * @return array{list<string>, list<string>}
     */
    private function resolveFiles(?string $path, array $excludedDirectories): array
    {
        if (null === $path) {
            $templateDirectory = $this->resolveDirectory(
                $this->projectDirectory . DIRECTORY_SEPARATOR . 'templates',
                'template',
            );
            $translationDirectory = $this->resolveTranslationDirectory();

            return [
                $this->twigFiles($templateDirectory, $excludedDirectories),
                $this->translationFiles($translationDirectory),
            ];
        }

        $resolvedPath = $this->resolvePath($path);
        if (is_file($resolvedPath)) {
            if (str_ends_with($resolvedPath, '.twig')) {
                return [[$resolvedPath], []];
            }

            if ($this->isManagedTranslationFile($resolvedPath)) {
                return [[], [$resolvedPath]];
            }

            throw new TranslationScanInputException(
                'The specified file must be a Twig template or a bundle-managed translation file.',
            );
        }

        if (!is_dir($resolvedPath)) {
            throw new TranslationScanInputException('The specified path must be a file or directory.');
        }

        $translationFiles = [];
        if (is_dir($this->translationDirectory)) {
            $translationDirectory = $this->resolveTranslationDirectory();
            if ($this->isPathInsideDirectory($translationDirectory, $resolvedPath)) {
                $translationFiles = $this->translationFiles($translationDirectory);
            }
        }

        return [
            $this->twigFiles($resolvedPath, $excludedDirectories),
            $translationFiles,
        ];
    }

    /**
     * @return list<array{
     *     file: string,
     *     line: int,
     *     type: 'text'|'attribute',
     *     value: string,
     *     attribute?: string
     * }>
     */
    private function scanTwigFile(string $file, int $minLength): array
    {
        $contents = $this->readFile($file);
        $relativePath = $this->relativePath($file);
        $sanitized = $this->sanitizeTemplate($contents);

        return [
            ...$this->findTextNodes($relativePath, $sanitized, $minLength),
            ...$this->findTranslatableAttributes($relativePath, $sanitized, $minLength),
        ];
    }

    /**
     * @return list<array{
     *     file: string,
     *     line: int,
     *     type: 'placeholder',
     *     key: string,
     *     value: string
     * }>
     */
    private function scanTranslationFile(string $file): array
    {
        $contents = $this->readFile($file);
        $relativePath = $this->relativePath($file);

        try {
            $entries = $this->codec->parse($contents);
        } catch (InvalidTranslationDocumentException $exception) {
            throw new TranslationScanException(sprintf(
                'The translation file "%s" is invalid: %s',
                $relativePath,
                $exception->getMessage(),
            ), previous: $exception);
        }

        $lines = preg_split('~\R~u', $contents);
        if (false === $lines) {
            throw new TranslationScanException(sprintf(
                'The translation file "%s" could not be inspected.',
                $relativePath,
            ));
        }

        $findings = [];
        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if ('' === $trimmed || str_starts_with($trimmed, '#')) {
                continue;
            }

            $separator = strpos($line, ':');
            if (false === $separator) {
                continue;
            }

            $key = trim(substr($line, 0, $separator));
            if (!isset($entries[$key]) || !str_starts_with($entries[$key], '__')) {
                continue;
            }

            $findings[] = [
                'file' => $relativePath,
                'line' => $index + 1,
                'type' => 'placeholder',
                'key' => $key,
                'value' => $entries[$key],
            ];
        }

        return $findings;
    }

    /**
     * Removes template regions that should not be inspected while preserving newline positions for accurate line reporting.
     */
    private function sanitizeTemplate(string $contents): string
    {
        $patterns = [
            '~\{#\s*translation-scan-ignore-start\s*#\}.*?\{#\s*translation-scan-ignore-end\s*#\}~su',
            '~\{#.*?#\}~su',
            '~<!--.*?-->~su',
            '~\{%\s*trans(?:\s.*?)?%\}.*?\{%\s*endtrans\s*%\}~su',
            '~<script\b[^>]*>.*?</script\s*>~isu',
            '~<style\b[^>]*>.*?</style\s*>~isu',
            '~\{\{.*?\}\}~su',
            '~\{%.*?%\}~su',
        ];

        foreach ($patterns as $pattern) {
            $contents = preg_replace_callback(
                $pattern,
                fn (array $match): string => $this->preserveNewlines($match[0]),
                $contents,
            ) ?? $contents;
        }

        return $contents;
    }

    /**
     * @return list<array{
     *     file: string,
     *     line: int,
     *     type: 'text',
     *     value: string
     * }>
     */
    private function findTextNodes(string $file, string $contents, int $minLength): array
    {
        $textOnly = preg_replace_callback(
            '~<[^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>~su',
            fn (array $match): string => $this->preserveNewlines($match[0]),
            $contents,
        ) ?? $contents;

        $lines = preg_split('~\R~u', $textOnly);
        if (false === $lines) {
            return [];
        }

        $findings = [];
        foreach ($lines as $index => $line) {
            $value = $this->normalizeText($line);
            if (!$this->isCandidate($value, $minLength)) {
                continue;
            }

            $findings[] = [
                'file' => $file,
                'line' => $index + 1,
                'type' => 'text',
                'value' => $value,
            ];
        }

        return $findings;
    }

    /**
     * @return list<array{
     *     file: string,
     *     line: int,
     *     type: 'attribute',
     *     attribute: string,
     *     value: string
     * }>
     */
    private function findTranslatableAttributes(string $file, string $contents, int $minLength): array
    {
        $attributeNames = implode(
            '|',
            array_map(
                static fn (string $attribute): string => preg_quote($attribute, '~'),
                self::TRANSLATABLE_ATTRIBUTES,
            ),
        );
        $pattern = sprintf(
            '~(?<![\w-])(?<attribute>%s)\s*=\s*(?<quote>["\'])(?<value>.*?)\k<quote>~isu',
            $attributeNames,
        );
        $matched = preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        if (false === $matched || 0 === $matched) {
            return [];
        }

        $findings = [];
        foreach ($matches as $match) {
            $value = $this->normalizeText($match['value'][0]);
            if (!$this->isCandidate($value, $minLength)) {
                continue;
            }

            $offset = $match[0][1];
            $findings[] = [
                'file' => $file,
                'line' => substr_count(substr($contents, 0, $offset), "\n") + 1,
                'type' => 'attribute',
                'attribute' => strtolower($match['attribute'][0]),
                'value' => $value,
            ];
        }

        return $findings;
    }

    /**
     * @param list<string> $excludedDirectories
     *
     * @return list<string>
     */
    private function twigFiles(string $directory, array $excludedDirectories): array
    {
        if (in_array(basename($directory), $excludedDirectories, true)) {
            return [];
        }

        try {
            $directoryIterator = new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS,
            );
            $filteredIterator = new \RecursiveCallbackFilterIterator(
                $directoryIterator,
                static function (
                    \SplFileInfo $current,
                    string $key,
                    \RecursiveIterator $iterator,
                ) use ($excludedDirectories): bool {
                    if ($current->isDir()) {
                        return !in_array($current->getFilename(), $excludedDirectories, true);
                    }

                    return $current->isFile() && str_ends_with($current->getFilename(), '.twig');
                },
            );
            $iterator = new \RecursiveIteratorIterator($filteredIterator);
            $files = [];
            foreach ($iterator as $file) {
                $files[] = $file->getRealPath() ?: $file->getPathname();
            }
        } catch (\UnexpectedValueException $exception) {
            throw new TranslationScanException(sprintf(
                'The template directory "%s" could not be inspected.',
                $this->relativePath($directory),
            ), previous: $exception);
        }

        sort($files, SORT_STRING);

        return $files;
    }

    /** @return list<string> */
    private function translationFiles(string $directory): array
    {
        $paths = glob($directory . DIRECTORY_SEPARATOR . '*.yaml');
        if (false === $paths) {
            throw new TranslationScanException('Unable to inspect the translation directory.');
        }

        $files = [];
        foreach ($paths as $path) {
            if (!is_file($path) || null === $this->codec->parseFilename(basename($path))) {
                continue;
            }

            $files[] = $path;
        }

        sort($files, SORT_STRING);

        return $files;
    }

    private function isManagedTranslationFile(string $path): bool
    {
        return dirname($path) === $this->resolveTranslationDirectory()
            && null !== $this->codec->parseFilename(basename($path));
    }

    private function resolvePath(string $path): string
    {
        $path = trim($path);
        if ('' === $path) {
            throw new TranslationScanInputException('The specified path cannot be empty.');
        }

        $candidate = (
            str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1
        )
            ? $path
            : rtrim($this->projectDirectory, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        $resolvedPath = realpath($candidate);

        if (false === $resolvedPath) {
            throw new TranslationScanInputException(sprintf(
                'The specified path does not exist: %s',
                $path,
            ));
        }

        return $resolvedPath;
    }

    private function resolveTranslationDirectory(): string
    {
        return $this->resolveDirectory($this->translationDirectory, 'translation');
    }

    private function resolveDirectory(string $directory, string $type): string
    {
        $resolvedDirectory = realpath($directory);
        if (false === $resolvedDirectory || !is_dir($resolvedDirectory) || !is_readable($resolvedDirectory)) {
            throw new TranslationScanException(sprintf(
                'The %s directory is unavailable: %s',
                $type,
                $directory,
            ));
        }

        return $resolvedDirectory;
    }

    private function readFile(string $file): string
    {
        $contents = file_get_contents($file);
        if (false === $contents) {
            throw new TranslationScanException(sprintf(
                'Could not read file: %s',
                $this->relativePath($file),
            ));
        }

        return $contents;
    }

    private function isPathInsideDirectory(string $path, string $directory): bool
    {
        $normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
        $normalizedDirectory = rtrim(str_replace('\\', '/', $directory), '/');

        if ('Windows' === PHP_OS_FAMILY) {
            $normalizedPath = strtolower($normalizedPath);
            $normalizedDirectory = strtolower($normalizedDirectory);
        }

        return $normalizedPath === $normalizedDirectory
            || str_starts_with($normalizedPath, $normalizedDirectory . '/');
    }

    private function relativePath(string $path): string
    {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedProjectDirectory = rtrim(
            str_replace('\\', '/', $this->projectDirectory),
            '/',
        );
        $prefix = $normalizedProjectDirectory . '/';

        if (str_starts_with($normalizedPath, $prefix)) {
            return substr($normalizedPath, strlen($prefix));
        }

        return $normalizedPath;
    }

    private function isCandidate(string $value, int $minLength): bool
    {
        if ('' === $value || mb_strlen($value) < $minLength || preg_match('~\p{L}~u', $value) !== 1) {
            return false;
        }

        if (preg_match('~^&(?:[a-z]+|#\d+|#x[a-f\d]+);$~i', $value) === 1) {
            return false;
        }

        if (preg_match('~^(?:https?://|mailto:|tel:|/|#)[^\s]+$~iu', $value) === 1) {
            return false;
        }

        return preg_match('~^[a-z][a-z\d_-]*(?:\.[a-z\d_-]+)+$~i', $value) !== 1;
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('~\s+~u', ' ', $value) ?? $value);
    }

    private function preserveNewlines(string $value): string
    {
        return preg_replace('~[^\r\n]~u', ' ', $value) ?? $value;
    }

    private function assertEnabled(): void
    {
        if (!$this->enabled) {
            throw new TranslationScanException('Translation management is disabled.');
        }
    }
}
