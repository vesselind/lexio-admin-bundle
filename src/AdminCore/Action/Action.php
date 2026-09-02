<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\AdminCore\Action;

use Lexio\AdminBundle\AdminCore\Fields\BaseField;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function Symfony\Component\String\u;

final class Action
{
    public ?BaseField $parentField = null;

    private bool    $openInModal       = false;
    private ?string $modalSize         = null;
    private ?string $textClass         = null;
    private bool    $confirmationModal = false;
    private ?string $confirmationText  = null;
    private ?string $confirmationRoute = null;

    /** @var array<string, string> */
    private array   $confirmationParams = [];

    /**
     * @param array<string, string> $routeParams
     */
    private function __construct(
        private readonly string  $label,
        private readonly string  $route,
        private readonly array   $routeParams = [],
        private readonly ?string $icon = null,
    ) {
    }

    /**
     * @param array<string, string> $routeParams
     */
    public static function new(string $label, string $route, array $routeParams = [], ?string $icon = null): static
    {
        return new self($label, $route, $routeParams, $icon);
    }

    public function setParentField(BaseField $parentField): static
    {
        $this->parentField = $parentField;

        return $this;
    }

    public function getEntityInstance(): object
    {
        if ($this->parentField === null) {
            throw new \LogicException('The parent field must be set before reading the entity instance.');
        }

        $entityInstance = $this->parentField->getEntityInstance();
        if ($entityInstance === null) {
            throw new \LogicException('The parent field entity instance must be set before reading an action route.');
        }

        return $entityInstance;
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

    /**
     * @return array<string, mixed>
     */
    public function getRouteParams(): array
    {
        $accessor       = PropertyAccess::createPropertyAccessor();
        $entityInstance = $this->getEntityInstance();

        return array_map(
            static fn (string $propertyPath): mixed => $accessor->getValue($entityInstance, $propertyPath),
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

    /**
     * @param array<string, string> $routeParams
     */
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
        if ($this->confirmationText === null) {
            throw new \LogicException('The confirmation modal must be configured before reading its text.');
        }

        return $this->confirmationText;
    }

    public function getConfirmationRoute(): string
    {
        if ($this->confirmationRoute === null) {
            throw new \LogicException('The confirmation modal must be configured before reading its route.');
        }

        return $this->confirmationRoute;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfirmationRouteParams(): array
    {
        $accessor       = PropertyAccess::createPropertyAccessor();
        $entityInstance = $this->getEntityInstance();

        return array_map(
            static fn (string $propertyPath): mixed => $accessor->getValue($entityInstance, $propertyPath),
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

