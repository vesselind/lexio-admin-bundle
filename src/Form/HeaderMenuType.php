<?php

namespace Lexio\AdminBundle\Form;

use Lexio\AdminBundle\Entity\HeaderMenu;
use Lexio\AdminBundle\Repository\HeaderMenuRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeaderMenuType extends AbstractType
{
    public function __construct(private readonly HeaderMenuRepository $headerMenuRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('parent', EntityType::class, [
                'label' => 'label.parent_menu',
                'class' => HeaderMenu::class,
                'choices' => $this->headerMenuRepository->findBy(['parent' => null]),
                'choice_label' => 'title',
                'autocomplete' => true,
                'required' => false
            ])
            ->add('title', TextType::class, [
                'label' => 'label.title'
            ])
            ->add('path', TextType::class, [
                'label' => 'label.path'
            ])
            ->add('Submit', SubmitType::class, [
                'label' => 'button.save'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HeaderMenu::class,
            'translation_domain' => 'admin'
        ]);
    }
}
