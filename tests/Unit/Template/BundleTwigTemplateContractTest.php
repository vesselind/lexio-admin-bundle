<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Template;

use Lexio\AdminBundle\LexioAdminBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    public function test_form_templates_expose_consistent_blocks_and_turbo_frames(): void
    {
        foreach ([
            'admin/base_crud/form.html.twig',
            'admin/page/form.html.twig',
        ] as $template) {
            $source = file_get_contents(dirname(__DIR__, 3) . '/templates/' . $template);

            self::assertIsString($source);
            self::assertMatchesRegularExpression(
                '/<turbo-frame id="main-form"[^>]*>\s*\{% block main_form %\}/',
                $source,
                sprintf('%s must expose the complete form region through main_form.', $template),
            );
            self::assertMatchesRegularExpression(
                '/<turbo-frame id="\{\{ content_frame \}\}">\s*\{% block main_form_content %\}/',
                $source,
                sprintf('%s must expose the nested form body through main_form_content.', $template),
            );
            self::assertStringContainsString(
                "requested_frame != 'main-form'",
                $source,
                sprintf('%s must prevent duplicate nested main-form frame IDs.', $template),
            );
        }

        $baseForm = file_get_contents(dirname(__DIR__, 3) . '/templates/admin/base_crud/form.html.twig');
        self::assertIsString($baseForm);
        self::assertStringContainsString('data-turbo-frame="main-form-content"', $baseForm);

        foreach ([
            'admin/base_crud/form_tab.html.twig',
            'admin/base_crud/seo.html.twig',
        ] as $template) {
            $source = file_get_contents(dirname(__DIR__, 3) . '/templates/' . $template);

            self::assertIsString($source);
            self::assertStringStartsWith('<turbo-frame id="main-form-content">', $source);
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
            'reprise_entry_link_tags',
            'reprise_entry_script_tags',
            'form_end',
            'form_errors',
            'form_help',
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
