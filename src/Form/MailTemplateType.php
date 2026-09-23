<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form;

use Lexio\AdminBundle\Entity\MailTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MailTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'label.name',
                'attr' => [
                    'readonly' => true,
                ],
                'required' => true,
            ])
            ->add('subject', TextType::class, [
                'label' => 'label.subject',
                'required' => true,
            ])
            ->add('enabled', CheckboxType::class, [
                'label' => 'label.enabled',
                'help' => 'help.mail_template_enabled',
                'required' => false,
            ])
            ->add('content', TextareaType::class, [
                'label' => 'label.content',
                'required' => true,
                'attr' => [
                    'rows' => 15,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MailTemplate::class,
            'translation_domain' => 'form',
        ]);
    }
}
