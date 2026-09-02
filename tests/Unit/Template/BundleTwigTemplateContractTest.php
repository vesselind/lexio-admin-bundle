<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Template;

use Lexio\AdminBundle\LexioAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Symfony\UX\StimulusBundle\Twig\StimulusTwigExtension;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Source;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class BundleTwigTemplateContractTest extends TestCase
{
    public function test_every_bundle_twig_template_compiles_with_strict_variables_enabled(): void
    {
        $templateDirectory = dirname(__DIR__, 3) . '/templates';
        $loader = new FilesystemLoader($templateDirectory);
        $loader->addPath($templateDirectory, 'LexioAdmin');

        $twig = new Environment($loader, [
            'strict_variables' => true,
        ]);
        $twig->addExtension(new ComponentExtension());
        $twig->addExtension(new StimulusTwigExtension(new StimulusHelper($twig)));
        $this->addCompileOnlyFunctions($twig);
        $this->addCompileOnlyFilters($twig);

        $compiledTemplates = 0;
        foreach ($this->templateFiles() as $path) {
            $source = file_get_contents($path);

            self::assertIsString($source);

            try {
                $twig->compileSource(new Source($source, $this->templateName($path), $path));
            } catch (Throwable $exception) {
                self::fail(sprintf(
                    'Bundle template %s failed to compile: %s',
                    $this->relativeTemplatePath($path),
                    $exception->getMessage(),
                ));
            }

            ++$compiledTemplates;
        }

        self::assertGreaterThan(0, $compiledTemplates);
    }

    public function test_templates_reference_only_declared_configured_route_keys(): void
    {
        $configuration = $this->processedBundleConfiguration();
        $routes = $configuration['ui']['routes'] ?? null;
        self::assertIsArray($routes);
        $routeKeys = array_keys($routes);

        foreach ($this->templateFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            preg_match_all(
                '/lexio_admin_ui\.routes\.([a-zA-Z_][a-zA-Z0-9_]*)/',
                $source,
                $matches,
            );

            foreach (array_unique($matches[1]) as $routeKey) {
                self::assertContains(
                    $routeKey,
                    $routeKeys,
                    sprintf('%s references an undeclared route key.', $this->relativeTemplatePath($path)),
                );
            }
        }
    }

    public function test_bundle_translation_catalogs_use_yaml_for_english_and_bulgarian_locales(): void
    {
        $directory = dirname(__DIR__, 3) . '/translations';
        $files = glob($directory . '/LexioAdminBundle.*') ?: [];
        $filenames = array_map(
            static fn (string $path): string => basename($path),
            $files,
        );
        sort($filenames);

        self::assertSame([
            'LexioAdminBundle.bg.yaml',
            'LexioAdminBundle.en.yaml',
        ], $filenames);
    }

    public function test_static_template_translation_keys_exist_in_every_bundle_catalog(): void
    {
        $catalogKeys = [];
        $catalogKeySets = [];
        foreach ($this->catalogFiles() as $catalog) {
            $translations = Yaml::parseFile($catalog);

            self::assertIsArray($translations);

            foreach ($translations as $key => $value) {
                self::assertIsString($key);
                self::assertIsString($value);
            }

            $keys = array_keys($translations);
            sort($keys);
            $catalogKeySets[$catalog] = $keys;
            $catalogKeys[$catalog] = array_fill_keys(array_keys($translations), true);
        }

        $catalogFiles = $this->catalogFiles();
        self::assertSame($catalogKeySets[$catalogFiles[0]], $catalogKeySets[$catalogFiles[1]]);

        foreach ($this->templateFiles() as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);

            preg_match_all(
                '/[\'\"]([a-zA-Z0-9_.-]+)[\'\"]\s*\|\s*trans\s*\([^)]*lexio_admin_ui\.translation_domain/s',
                $source,
                $matches,
            );

            foreach (array_unique($matches[1]) as $translationKey) {
                foreach ($catalogKeys as $catalog => $keys) {
                    self::assertArrayHasKey(
                        $translationKey,
                        $keys,
                        sprintf(
                            '%s uses %s, which is missing from %s.',
                            $this->relativeTemplatePath($path),
                            $translationKey,
                            basename($catalog),
                        ),
                    );
                }
            }
        }
    }

    /** @return list<string> */
    private function templateFiles(): array
    {
        $directory = dirname(__DIR__, 3) . '/templates';
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.html.twig')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /** @return list<string> */
    private function catalogFiles(): array
    {
        $directory = dirname(__DIR__, 3) . '/translations';

        return [
            $directory . '/LexioAdminBundle.en.yaml',
            $directory . '/LexioAdminBundle.bg.yaml',
        ];
    }

    /** @return array<string, mixed> */
    private function processedBundleConfiguration(): array
    {
        $container = new ContainerBuilder();
        $extension = (new LexioAdminBundle())->getContainerExtension();

        self::assertNotNull($extension);
        $configuration = $extension->getConfiguration([], $container);
        self::assertNotNull($configuration);

        return (new Processor())->processConfiguration($configuration, [[]]);
    }

    private function relativeTemplatePath(string $path): string
    {
        return str_replace(
            '\\',
            '/',
            ltrim(str_replace(dirname(__DIR__, 3), '', $path), DIRECTORY_SEPARATOR),
        );
    }

    private function templateName(string $path): string
    {
        $templatesDirectory = str_replace('\\', '/', dirname(__DIR__, 3) . '/templates');
        $normalizedPath = str_replace('\\', '/', $path);

        return '@LexioAdmin/' . ltrim(str_replace($templatesDirectory, '', $normalizedPath), '/');
    }

    private function addCompileOnlyFunctions(Environment $twig): void
    {
        foreach ([
            'asset',
            'csrf_token',
            'encore_entry_link_tags',
            'encore_entry_script_tags',
            'form_end',
            'form_label',
            'form_row',
            'form_start',
            'form_widget',
            'is_granted',
            'knp_pagination_render',
            'path',
            'render_field',
            'url',
            'ux_icon',
            'wo_render_breadcrumbs',
        ] as $functionName) {
            $twig->addFunction(new TwigFunction($functionName, static fn (...$arguments): string => ''));
        }
    }

    private function addCompileOnlyFilters(Environment $twig): void
    {
        foreach ([
            'format_bytes',
            'formatted_date',
            'formatted_datetime',
            'image',
            'strip_words',
            'trans',
        ] as $filterName) {
            $twig->addFilter(new TwigFilter($filterName, static fn (mixed $value, ...$arguments): mixed => $value));
        }
    }
}
