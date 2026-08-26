<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\Filter;

use Lexio\AdminBundle\Utils\AdminUtils;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Auto-generating base filter form type.
 *
 * Inspects the filter DTO via reflection and adds a form field for every
 * typed property:
 *   - string  → text input with placeholder
 *   - bool    → checkbox with label
 *   - Entity  → EntityType (autocomplete)
 *   - Enum    → EnumType  (autocomplete)
 *
 * Use with `$listing->setFilter($filter, BaseFilterType::class)`.
 */
class BaseFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['data'] === null) {
            return;
        }

        $reflection = new \ReflectionClass($options['data']);

        foreach ($reflection->getProperties() as $property) {
            if ($property->getType() instanceof \ReflectionUnionType) {
                continue;
            }

            /** @phpstan-ignore-next-line */
            $fieldType = $property->getType()?->getName();
            $fieldName = $property->getName();

            if ($fieldType === 'string') {
                $builder->add($fieldName, null, [
                    'label' => false,
                    'attr'  => [
                        'placeholder' => 'placeholder.' . AdminUtils::toSnakeCase($fieldName),
                    ],
                ]);
            } elseif ($fieldType === 'bool') {
                $builder->add($fieldName, CheckboxType::class, [
                    'label'    => 'label.' . AdminUtils::toSnakeCase($fieldName),
                    'required' => false,
                ]);
            } elseif ($property->getType() !== null && !$property->getType()->isBuiltin()) {
                // Detect if it's a Doctrine entity
                if (class_exists($fieldType) && str_contains($fieldType, 'Entity')) {
                    $builder->add($fieldName, EntityType::class, [
                        'class'        => $fieldType,
                        'label'        => false,
                        'autocomplete' => true,
                        'placeholder'  => 'placeholder.' . AdminUtils::toSnakeCase($fieldName),
                    ]);
                } elseif (class_exists($fieldType) && is_a($fieldType, \BackedEnum::class, true)) {
                    $builder->add($fieldName, EnumType::class, [
                        'class'        => $fieldType,
                        'label'        => false,
                        'autocomplete' => true,
                        'placeholder'  => 'placeholder.' . AdminUtils::toSnakeCase($fieldName),
                    ]);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'         => null,
            'translation_domain' => 'form',
            'required'           => false,
            'csrf_protection'    => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'f';
    }
}

