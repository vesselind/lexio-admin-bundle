<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Cloudflare Turnstile / CAPTCHA hidden field.
 *
 * Wires up the `captcha` Stimulus controller with the configured site key.
 *
 * The validation constraint (`CheckedCaptcha` or equivalent) must be supplied
 * by the host application via the `constraints` form option, since the validator
 * logic is application-specific.
 *
 * Usage in your app:
 *   $builder->add('captcha', CaptchaType::class, [
 *       'constraints' => [new \App\Validator\CheckedCaptcha()],
 *   ]);
 */
class CaptchaType extends AbstractType
{
    public function __construct(private readonly string $turnstileKey)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'      => false,
            'constraints' => [],   // Provide app-specific constraint via this option.
            'attr'        => [
                'data-controller' => 'captcha',
                'data-api-key'    => $this->turnstileKey,
            ],
        ]);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'recaptcha';
    }
}

