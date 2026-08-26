<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\CustomFields;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An EntityType wrapper that opens a modal dialog for creating / searching associations.
 *
 * Usage in a FormType:
 *   $builder->add('category', AssociationModalType::class, [
 *       'class'      => Category::class,
 *       'visit_url'  => $this->router->generate('admin.category.create'),
 *       'modal_title' => 'modal.create_category',
 *   ]);
 */
class AssociationModalType extends AbstractType
{
    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => true,
            'multiple'     => false,
            'visit_url'    => null,
            'choice_label' => 'title',
            'modal_title'  => 'modal.create',
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $visitUrl  = $options['visit_url'] ?? '';
        $separator = str_contains($visitUrl, '?') ? '&' : '?';

        $visitUrl .= $separator . http_build_query([
            '_modal_context'       => 'association',
            '_modal_entity_class'  => $options['class'],
            '_modal_choice_label'  => $options['choice_label'],
        ]);

        $view->vars['visitUrl']   = $visitUrl;
        $view->vars['class']      = $options['class'];
        $view->vars['modalTitle'] = $options['modal_title'];
    }
}

