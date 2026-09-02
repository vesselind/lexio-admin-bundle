<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Cloudflare Turnstile hidden field.
 *
 * Wires up the `turnstile` Stimulus controller with the configured site key.
 * The optional `site_key` form option overrides that fallback per form.
 *
 * The validation constraint (`CheckedTurnstile` or equivalent) must be supplied
 * by the host application via the `constraints` form option, since the validator
 * logic is application-specific.
 *
 * Usage in your app:
 *   $builder->add('turnstile', TurnstileType::class, [
 *       'constraints' => [new \App\Validator\CheckedTurnstile()],
 *   ]);
 */
final class TurnstileType extends AbstractType
{
    public function __construct(private readonly ?string $turnstileKey = null)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'      => false,
            'constraints' => [],   // Provide app-specific constraint via this option.
            'site_key' => $this->turnstileKey ?? '',
            'attr'        => [
                'data-controller' => 'turnstile',
            ],
        ]);
        $resolver->setAllowedTypes('site_key', 'string');
    }

    /** @param array<string, mixed> $options */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $attributes = $view->vars['attr'];
        $attributes['data-controller'] = self::appendController(
            (string) ($attributes['data-controller'] ?? ''),
            'turnstile',
        );
        $attributes['data-turnstile-api-key-value'] = $options['site_key'];

        $view->vars['attr'] = $attributes;
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'turnstile';
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
