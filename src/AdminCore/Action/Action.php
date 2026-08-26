<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Action;

use Lexio\AdminBundle\AdminCore\Fields\BaseField;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function Symfony\Component\String\u;

class Action
{
    public ?BaseField $parentField = null;

    private bool    $openInModal       = false;
    private ?string $modalSize         = null;
    private ?string $textClass         = null;
    private bool    $confirmationModal = false;
    private ?string $confirmationText  = null;
    private ?string $confirmationRoute = null;
    private array   $confirmationParams = [];

    private function __construct(
        private readonly string  $label,
        private readonly string  $route,
        private readonly array   $routeParams = [],
        private readonly ?string $icon = null,
    ) {
    }

    public static function new(string $label, string $route, array $routeParams = [], ?string $icon = null): static
    {
        return new static($label, $route, $routeParams, $icon);
    }

    public function setParentField(BaseField $parentField): static
    {
        $this->parentField = $parentField;

        return $this;
    }

    public function getEntityInstance(): object
    {
        return $this->parentField->getEntityInstance();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSnakeLabel(): string
    {
        return u($this->label)->replace('.', '_')->snake()->toString();
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function hasRouteParams(): bool
    {
        return !empty($this->routeParams);
    }

    public function getRouteParams(): array
    {
        $accessor       = PropertyAccess::createPropertyAccessor();
        $entityInstance = $this->getEntityInstance();

        return array_map(
            static fn (mixed $propertyPath) => $accessor->getValue($entityInstance, $propertyPath),
            $this->routeParams
        );
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function openInModal(string $modalSize): static
    {
        $this->openInModal = true;
        $this->modalSize   = $modalSize;

        return $this;
    }

    public function isOpenInModal(): bool
    {
        return $this->openInModal;
    }

    public function confirmationModal(string $confirmationText, string $route, array $routeParams = []): static
    {
        $this->confirmationModal  = true;
        $this->confirmationText   = $confirmationText;
        $this->confirmationRoute  = $route;
        $this->confirmationParams = $routeParams;

        return $this;
    }

    public function hasConfirmationModal(): bool
    {
        return $this->confirmationModal;
    }

    public function getConfirmationModalText(): string
    {
        return $this->confirmationText;
    }

    public function getConfirmationRoute(): string
    {
        return $this->confirmationRoute;
    }

    public function getConfirmationRouteParams(): array
    {
        $accessor       = PropertyAccess::createPropertyAccessor();
        $entityInstance = $this->getEntityInstance();

        return array_map(
            static fn (mixed $propertyPath) => $accessor->getValue($entityInstance, $propertyPath),
            $this->confirmationParams
        );
    }

    public function getModalSize(): ?string
    {
        return $this->modalSize;
    }

    public function setTextClass(string $textClass): static
    {
        $this->textClass = $textClass;

        return $this;
    }

    public function getTextClass(): string
    {
        return $this->textClass ?? '';
    }
}

