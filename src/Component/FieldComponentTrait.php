<?php

namespace Lexio\AdminBundle\Component;
use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\AdminCore\AdminUrlGenerator;
use Lexio\AdminBundle\AdminCore\Fields\BaseField;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

trait FieldComponentTrait
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public mixed $fieldValue = null;

    #[LiveProp(writable: true)]
    public ?int $entityId = null;

    /** @var class-string|null */
    #[LiveProp(writable: true)]
    public ?string $entityFqcn = null;

    #[LiveProp(writable: true)]
    public ?string $propertyName = null;

    public ?BaseField $field = null;

    public ?AdminUrlGenerator $adminUrlGenerator = null;

    public function mount(BaseField $field): void
    {
        $column = $field->getColumn();
        if ($column === null) {
            throw new \LogicException('A field column must be set before mounting its live component.');
        }

        $this->entityFqcn = $column->getEntityFqcn();
        $this->entityId = $field->getEntityInstance()?->getId();
        $this->propertyName = $column->propertyName;
        $this->fieldValue = $field->getValue();
    }

    protected function getEntityData(): ?object
    {
        if ($this->entityFqcn === null || $this->entityId === null) {
            return null;
        }

        return $this->manager()->getRepository($this->entityFqcn)->find($this->entityId);
    }

    abstract public function manager(): EntityManagerInterface;
}
