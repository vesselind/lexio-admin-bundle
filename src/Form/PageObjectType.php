<?php

namespace Lexio\AdminBundle\Form;

use Lexio\AdminBundle\Attributes\FieldType;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\Service\Utils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageObjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflection = new \ReflectionClass($options['data']);
        $fields = $reflection->getProperties();

        $builder->add('title', TextType::class, [
            'label' => 'label.title',
        ]);

        foreach ($fields as $field) {

            $fieldTypeAttribute = null;

            foreach ($field->getAttributes() as $attribute) {
                if ($attribute->getName() === FieldType::class) {
                    $fieldTypeAttribute = $attribute;

                    break;
                }
            }

            if ($fieldTypeAttribute === null || $field->getName() === 'title') {
                continue;
            }

            /** @var ContentItemTypes $contentItemType */
            $contentItemType = $fieldTypeAttribute->getArguments()[0];


            $builder->add($field->getName(), $contentItemType->getFormTypeClass(), [
                'label' => 'label.' . Utils::toSnakeCase($field->getName()),
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'admin',
            'required' => false,
        ]);
    }
}
