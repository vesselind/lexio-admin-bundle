<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Generic SEO form type.
 *
 * Maps common SEO fields onto any entity that carries them.
 * Fields that do not exist on the bound entity are silently ignored
 * because the form is created with `data_class` pointing to the entity FQCN.
 *
 * Standard SEO fields assumed on the entity:
 *   - metaTitle    (string, nullable)
 *   - metaDescription (string, nullable)
 *   - metaKeywords (string, nullable)
 *   - ogTitle      (string, nullable)
 *   - ogDescription (string, nullable)
 *
 * Override this type in your application to add or remove fields.
 */
class SeoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('metaTitle', TextType::class, [
                'label'    => 'label.meta_title',
                'required' => false,
            ])
            ->add('metaDescription', TextareaType::class, [
                'label'    => 'label.meta_description',
                'required' => false,
                'attr'     => ['rows' => 3],
            ])
            ->add('metaKeywords', TextType::class, [
                'label'    => 'label.meta_keywords',
                'required' => false,
            ])
            ->add('ogTitle', TextType::class, [
                'label'    => 'label.og_title',
                'required' => false,
            ])
            ->add('ogDescription', TextareaType::class, [
                'label'    => 'label.og_description',
                'required' => false,
                'attr'     => ['rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => null,
            'translation_domain' => 'form',
        ]);
    }
}

