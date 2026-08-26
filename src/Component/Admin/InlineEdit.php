<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Component\FieldComponentTrait;
use Lexio\AdminBundle\Controller\BaseController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Generic inline-edit live component.
 *
 * Usage in Twig:
 *   <twig:Admin:InlineEdit
 *       entityClass="App\Entity\Post"
 *       entityId="{{ post.id }}"
 *       property="title"
 *       content="{{ post.title }}"
 *   />
 */
#[AsLiveComponent(name: 'Admin:InlineEdit', template: '@LexioAdmin/components/Admin/InlineEdit.html.twig')]
final class InlineEdit extends BaseController
{
    use DefaultActionTrait;

    use FieldComponentTrait;

    #[LiveProp(writable: true)]
    public string $content = '';

    #[LiveProp(writable: true)]
    public bool $editing = false;

    /** Fully-qualified entity class name (non-live prop, set at mount time). */
    public string $entityClass = '';

    /** Property name to update via setter (non-live prop, set at mount time). */
    public string $property = '';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }


    public function getContent(): string
    {
        return $this->content;
    }

    #[LiveAction]
    public function save(): void
    {
        $entity = $this->entityManager->find($this->entityClass, $this->entityId);

        if ($entity !== null) {
            $setter = 'set' . ucfirst($this->property);
            if (method_exists($entity, $setter)) {
                $entity->$setter($this->content);
                $this->entityManager->flush();
            }
        }

        $this->editing = false;
    }

    #[LiveAction]
    public function toggleEditing(): void
    {
        $this->editing = !$this->editing;
    }
}

