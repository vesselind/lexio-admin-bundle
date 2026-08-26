<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A Symfony DateType that activates the VanillaJS datepicker Stimulus controller.
 *
 * Reference: https://raw.githack.com/mymth/vanillajs-datepicker/v1.3.4/demo/index.html
 *
 * Requires a Stimulus controller named `vanilla-datepicker` (or `vanilla-datepicker-live`
 * for LiveComponent usage) to be registered.
 */
class VanillaDatepickerType extends AbstractType
{
    public function __construct(private readonly string $defaultLocale)
    {
    }

    public function getParent(): string
    {
        return DateType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget'          => 'single_text',
            'html5'           => false,
            'format'          => 'dd.MM.yyyy',
            'vanilla_format'  => 'dd.mm.yyyy',
            'min_date'        => null,
            'max_date'        => null,
            'live_component'  => false,
            'week_start'      => 1,
            'locale'          => $this->defaultLocale,
            'attr'            => function (Options $options): array {
                $controller = $options['live_component']
                    ? 'vanilla-datepicker-live'
                    : 'vanilla-datepicker';

                return [
                    'autocomplete'                        => 'off',
                    'data-controller'                     => $controller,
                    "data-{$controller}-format-value"     => $options['vanilla_format'],
                    "data-{$controller}-min-date-value"   => $options['min_date'],
                    "data-{$controller}-max-date-value"   => $options['max_date'],
                    "data-{$controller}-locale-value"     => $options['locale'],
                ];
            },
        ]);
    }
}

