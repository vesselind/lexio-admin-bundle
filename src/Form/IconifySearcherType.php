<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class IconifySearcherType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => true,
            'autocomplete_url' => $this->urlGenerator->generate('ux_autocomplete', [
                'alias' => 'iconify',
            ]),
            'attr' => [
                'placeholder' => 'placeholder.icon',
            ],
            'label' => 'label.icon',
            'min_characters' => 2,
            'options_as_html' => true,
            'tom_select_options' => [
                'maxItems' => 1,
            ],
            'preload' => false,
            'required' => false,
            'translation_domain' => 'form',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
