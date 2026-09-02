<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Google reCAPTCHA Enterprise hidden field.
 *
 * The optional `site_key` form option overrides the configured fallback. Use
 * `captcha_action` to bind the generated token to the server-side assessment.
 *
 * The host application must provide its server-side token validation constraint
 * through the standard `constraints` form option.
 */
final class CaptchaType extends AbstractType
{
    public function __construct(private readonly ?string $defaultSiteKey = null)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'constraints' => [],
            'site_key' => $this->defaultSiteKey ?? '',
            'captcha_action' => 'submit',
        ]);
        $resolver->setAllowedTypes('site_key', 'string');
        $resolver->setAllowedTypes('captcha_action', 'string');
        $resolver->setAllowedValues(
            'captcha_action',
            static fn (string $action): bool => trim($action) !== '',
        );
    }

    /** @param array<string, mixed> $options */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attributes = $view->vars['attr'];
        $attributes['data-controller'] = self::appendController(
            (string) ($attributes['data-controller'] ?? ''),
            'captcha',
        );
        $attributes['data-captcha-site-key-value'] = $options['site_key'];
        $attributes['data-captcha-action-value'] = $options['captcha_action'];

        $view->vars['attr'] = $attributes;
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'captcha';
    }

    private static function appendController(string $controllers, string $controller): string
    {
        $controllerNames = preg_split('/\s+/', trim($controllers), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (!in_array($controller, $controllerNames, true)) {
            $controllerNames[] = $controller;
        }

        return implode(' ', $controllerNames);
    }
}
