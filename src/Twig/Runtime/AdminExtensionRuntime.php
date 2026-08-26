<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Twig\Runtime;

use Lexio\AdminBundle\AdminCore\AdminUrlGenerator;
use Lexio\AdminBundle\AdminCore\Listing\Column;
use Lexio\AdminBundle\AdminCore\Menu\MenuBuilder;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

/**
 * Runtime for AdminExtension Twig functions.
 */
final class AdminExtensionRuntime implements RuntimeExtensionInterface, ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface        $locator,
        private readonly ComponentRendererInterface $componentRenderer,
        private readonly Environment               $environment,
        private readonly AdminUrlGenerator         $adminUrlGenerator,
    ) {
    }

    /**
     * Renders a listing Column's field using either its Twig component or its template.
     */
    public function renderField(object $entityInstance, Column $column): Markup
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $column->field->setEntityInstance($entityInstance);

        if ($column->getField()->mapped()) {
            try {
                $value = $propertyAccessor->getValue($entityInstance, $column->propertyName);
            } catch (UnexpectedTypeException) {
                $value = null;
            }

            $column->getField()->setValue($value);
        }

        if ($componentName = $column->getField()->twigComponent()) {
            return new Markup(
                $this->componentRenderer->createAndRender($componentName, [
                    'field'             => $column->getField(),
                    'adminUrlGenerator' => $this->adminUrlGenerator,
                ]),
                'utf-8'
            );
        }

        return new Markup(
            $this->environment->render($column->getField()->templatePath(), [
                'column'            => $column,
                'field'             => $column->getField(),
                'adminUrlGenerator' => $this->adminUrlGenerator,
            ]),
            'utf-8'
        );
    }

    public static function getSubscribedServices(): array
    {
        return [
            MenuBuilder::class,
        ];
    }
}


