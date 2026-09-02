<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form\CustomFields;

use Lexio\AdminBundle\Form\CustomFields\AssociationModalType;
use Lexio\AdminBundle\Form\CustomFields\CaptchaType;
use Lexio\AdminBundle\Form\CustomFields\CKEditorType;
use Lexio\AdminBundle\Form\CustomFields\InputImageSelectorType;
use Lexio\AdminBundle\Form\CustomFields\TurnstileType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CustomFieldTypeViewContractTest extends TestCase
{
    public function test_association_modal_exposes_the_view_contract_used_by_its_theme(): void
    {
        $view = new FormView();

        (new AssociationModalType())->buildView(
            $view,
            $this->createStub(FormInterface::class),
            [
                'visit_url' => '/admin/category/create?source=blog',
                'class' => 'App\\Entity\\Category',
                'choice_label' => 'title',
                'modal_title' => 'modal.create_category',
            ],
        );

        self::assertSame('App\\Entity\\Category', $view->vars['class']);
        self::assertSame('modal.create_category', $view->vars['modalTitle']);

        self::assertSame(
            '/admin/category/create?source=blog&_modal_context=association'
            . '&_modal_entity_class=App%5CEntity%5CCategory&_modal_choice_label=title',
            $view->vars['visitUrl'],
        );
    }

    public function test_ck_editor_exposes_the_links_search_view_contract_used_by_its_theme(): void
    {
        $type = new CKEditorType($this->createStub(RouterInterface::class));
        $view = new FormView();

        $type->buildView(
            $view,
            $this->createStub(FormInterface::class),
            ['links_search' => false],
        );

        self::assertFalse($view->vars['linksSearch']);
    }

    public function test_custom_field_block_prefixes_match_their_theme_contracts(): void
    {
        self::assertSame('association_modal', (new AssociationModalType())->getBlockPrefix());
        self::assertSame(
            'ck_editor',
            (new CKEditorType($this->createStub(RouterInterface::class)))->getBlockPrefix(),
        );
    }

    public function test_input_image_selector_uses_configured_route_and_translation_domain(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::once())
            ->method('generate')
            ->with('app.image_gallery')
            ->willReturn('/media/gallery');

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::once())
            ->method('trans')
            ->with('modal.gallery', [], 'AppAdmin')
            ->willReturn('Gallery');

        $resolver = new OptionsResolver();
        $type = new InputImageSelectorType($router, $translator, 'app.image_gallery', 'AppAdmin');
        $type->configureOptions($resolver);
        $options = $resolver->resolve();

        self::assertSame('/media/gallery', $options['attr']['data-open-base-modal-visit-url-value']);
        self::assertSame('Gallery', $options['attr']['data-open-base-modal-modal-title-value']);
    }

    public function test_turnstile_exposes_its_provider_specific_stimulus_contract(): void
    {
        $unconfiguredType = new TurnstileType();
        $unconfiguredResolver = new OptionsResolver();
        $unconfiguredType->configureOptions($unconfiguredResolver);
        self::assertSame('', $unconfiguredResolver->resolve()['site_key']);

        $type = new TurnstileType('turnstile-site-key');
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);
        self::assertSame('turnstile-site-key', $resolver->resolve()['site_key']);

        $options = $resolver->resolve(['site_key' => 'form-turnstile-site-key']);
        $view = new FormView();
        $view->vars['attr'] = ['data-controller' => 'other-controller'];
        $type->buildView($view, $this->createStub(FormInterface::class), $options);

        self::assertFalse($options['mapped']);
        self::assertSame([], $options['constraints']);
        self::assertSame('other-controller turnstile', $view->vars['attr']['data-controller']);
        self::assertSame('form-turnstile-site-key', $view->vars['attr']['data-turnstile-api-key-value']);
        self::assertSame(HiddenType::class, $type->getParent());
        self::assertSame('turnstile', $type->getBlockPrefix());
    }

    public function test_google_recaptcha_exposes_a_generic_stimulus_contract_without_constraints(): void
    {
        $type = new CaptchaType('configured-google-site-key');
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        self::assertSame('configured-google-site-key', $resolver->resolve()['site_key']);

        $options = $resolver->resolve([
            'site_key' => 'form-google-site-key',
            'captcha_action' => 'contact',
        ]);
        $view = new FormView();
        $view->vars['attr'] = ['data-controller' => 'other-controller'];
        $type->buildView($view, $this->createStub(FormInterface::class), $options);

        self::assertFalse($options['mapped']);
        self::assertSame([], $options['constraints']);
        self::assertSame('other-controller captcha', $view->vars['attr']['data-controller']);
        self::assertSame('form-google-site-key', $view->vars['attr']['data-captcha-site-key-value']);
        self::assertSame('contact', $view->vars['attr']['data-captcha-action-value']);
        self::assertSame(HiddenType::class, $type->getParent());
        self::assertSame('captcha', $type->getBlockPrefix());
    }

    public function test_google_recaptcha_action_must_not_be_blank(): void
    {
        $resolver = new OptionsResolver();
        (new CaptchaType())->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $resolver->resolve(['captcha_action' => ' ']);
    }
}
