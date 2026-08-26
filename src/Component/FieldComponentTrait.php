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

    #[LiveProp(writable: true)]
    public ?string $entityFqcn = null;

    #[LiveProp(writable: true)]
    public ?string $propertyName = null;

    public ?BaseField $field = null;

    public ?AdminUrlGenerator $adminUrlGenerator = null;

    public function mount(BaseField $field): void
    {
        $this->entityFqcn = $field->getColumn()?->getEntityFqcn();
        $this->entityId = $field->getEntityInstance()?->getId();
        $this->propertyName = $field->getColumn()->propertyName;
        $this->fieldValue = $field->getValue();
    }

    protected function getEntityData(): ?object
    {
        return $this->manager()->getRepository($this->entityFqcn)->find($this->entityId);
    }

    abstract public function manager(): EntityManagerInterface;
}