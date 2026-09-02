<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form\CustomFields;

use Lexio\AdminBundle\Form\CustomFields\AssociationModalType;
use Lexio\AdminBundle\Form\CustomFields\CKEditorType;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Test\FormLayoutTestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class CustomFieldFormThemeRenderingTest extends FormLayoutTestCase
{
    public function test_association_modal_widget_renders_through_the_bundle_theme(): void
    {
        $form = $this->factory->createNamed('category', AssociationModalThemeTestType::class);
        $view = $form->createView();

        (new AssociationModalType())->buildView($view, $form, [
            'visit_url' => '/admin/category/create',
            'class' => 'App\\Entity\\Category',
            'choice_label' => 'title',
            'modal_title' => 'modal.create_category',
        ]);

        $html = $this->renderWidget($view);

        self::assertStringContainsString('class="input-group"', $html);
        self::assertStringContainsString('data-association-modal-type-target="selectItem"', $html);
        self::assertStringContainsString('data-controller="association-modal-type"', $html);
        self::assertStringContainsString('data-controller="open-base-modal"', $html);
        self::assertStringContainsString('aria-label="modal.create_category"', $html);
    }

    public function test_ck_editor_label_renders_link_search_when_enabled(): void
    {
        $view = $this->factory
            ->createNamed('content', CKEditorType::class, null, [
                'label' => 'Content',
                'links_search' => true,
            ])
            ->createView();

        $html = $this->renderLabel($view);
        $modalLabel = 'addLink_' . $view->vars['unique_block_prefix'] . '_label';

        self::assertStringContainsString('Content', $html);
        self::assertStringContainsString('data-controller="links-search-field"', $html);
        self::assertStringContainsString('data-controller="navigate-turbo"', $html);
        self::assertStringContainsString('aria-label="tooltip.add_internal_link"', $html);
        self::assertStringContainsString('aria-label="button.close"', $html);
        self::assertStringContainsString('aria-labelledby="' . $modalLabel . '"', $html);
        self::assertStringContainsString('id="' . $modalLabel . '"', $html);
        self::assertStringContainsString('<turbo-frame', $html);
    }

    public function test_ck_editor_modals_use_unique_label_ids(): void
    {
        $firstView = $this->factory
            ->createNamed('introduction', CKEditorType::class, null, ['links_search' => true])
            ->createView();
        $secondView = $this->factory
            ->createNamed('conclusion', CKEditorType::class, null, ['links_search' => true])
            ->createView();

        $firstHtml = $this->renderLabel($firstView);
        $secondHtml = $this->renderLabel($secondView);
        $firstModalLabel = 'addLink_' . $firstView->vars['unique_block_prefix'] . '_label';
        $secondModalLabel = 'addLink_' . $secondView->vars['unique_block_prefix'] . '_label';

        self::assertNotSame($firstModalLabel, $secondModalLabel);
        self::assertStringContainsString('aria-labelledby="' . $firstModalLabel . '"', $firstHtml);
        self::assertStringContainsString('id="' . $firstModalLabel . '"', $firstHtml);
        self::assertStringNotContainsString($secondModalLabel, $firstHtml);
        self::assertStringContainsString('aria-labelledby="' . $secondModalLabel . '"', $secondHtml);
        self::assertStringContainsString('id="' . $secondModalLabel . '"', $secondHtml);
        self::assertStringNotContainsString($firstModalLabel, $secondHtml);
    }

    public function test_ck_editor_label_omits_link_search_when_disabled(): void
    {
        $view = $this->factory
            ->createNamed('content_without_links', CKEditorType::class, null, [
                'label' => 'Content',
                'links_search' => false,
            ])
            ->createView();

        $html = $this->renderLabel($view);

        self::assertStringContainsString('Content', $html);
        self::assertStringNotContainsString('links-search-field', $html);
        self::assertStringNotContainsString('<turbo-frame', $html);
    }

    public function test_host_theme_can_override_a_bundle_custom_field_block(): void
    {
        $view = $this->factory
            ->createNamed('overridden_content', CKEditorType::class)
            ->createView();

        $this->setTheme($view, ['host_override_theme.html.twig']);

        $html = $this->renderLabel($view);

        self::assertStringContainsString('data-host-override="true"', $html);
        self::assertStringNotContainsString('links-search-field', $html);
    }

    public function test_ordinary_text_fields_keep_the_host_bootstrap_theme(): void
    {
        $view = $this->factory
            ->createNamed('title', TextType::class)
            ->createView();

        self::assertStringContainsString('class="form-control"', $this->renderWidget($view));
    }

    /** @return list<PreloadedExtension> */
    protected function getExtensions(): array
    {
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('/admin/ckeditor/upload');

        return [
            new PreloadedExtension([
                new CKEditorType($router),
                new AssociationModalThemeTestType(),
            ], []),
        ];
    }

    /** @return list<string> */
    protected function getTemplatePaths(): array
    {
        return [
            __DIR__ . '/../../../Fixtures/templates/form',
            __DIR__ . '/../../../../templates/form',
            __DIR__ . '/../../../../vendor/symfony/twig-bridge/Resources/views/Form',
        ];
    }

    /** @return list<AbstractExtension> */
    protected function getTwigExtensions(): array
    {
        return [
            new FormExtension(),
            new CustomFieldThemeTwigExtension(),
        ];
    }

    /** @return list<string> */
    protected function getThemes(): array
    {
        return [
            'form_div_layout.html.twig',
            'custom_fields_theme.html.twig',
            'bootstrap_5_layout.html.twig',
        ];
    }
}

final class AssociationModalThemeTestType extends AbstractType
{
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'association_modal';
    }
}

final class CustomFieldThemeTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'lexio_admin_ui' => [
                'translation_domain' => 'admin',
                'routes' => [
                    'links_search' => 'admin._modals.links_search',
                ],
            ],
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'stimulus_controller',
                self::renderStimulusController(...),
                ['is_safe' => ['html_attr']],
            ),
            new TwigFunction(
                'path',
                static fn (string $route, array $parameters = []): string => '/' . $route
                    . ([] === $parameters ? '' : '?' . http_build_query($parameters)),
            ),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'stimulus_controller',
                self::appendStimulusController(...),
                ['is_safe' => ['html_attr']],
            ),
            new TwigFilter(
                'trans',
                static fn (mixed $message, array $parameters = [], ?string $domain = null): string => (string) $message,
            ),
        ];
    }

    /** @param array<string, mixed> $values */
    private static function renderStimulusController(string $controller, array $values = []): string
    {
        return 'data-controller="' . $controller . '"';
    }

    /** @param array<string, mixed> $values */
    private static function appendStimulusController(
        string $attributes,
        string $controller,
        array $values = [],
    ): string {
        return $attributes . ' data-controller="' . $controller . '"';
    }
}
