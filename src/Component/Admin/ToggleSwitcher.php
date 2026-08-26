<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Lexio\AdminBundle\Component\FieldComponentTrait;
use Lexio\AdminBundle\Enum\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Generic boolean toggle switcher live component.
 *
 * Usage in Twig:
 *   <twig:Admin:ToggleSwitcher
 *       entityClass="App\Entity\Post"
 *       entityId="{{ post.id }}"
 *       property="published"
 *       fieldValue="{{ post.published ? 'true' : 'false' }}"
 *   />
 */
#[AsLiveComponent(name: 'Admin:ToggleSwitcher', template: '@LexioAdmin/components/Admin/ToggleSwitcher.html.twig')]
class ToggleSwitcher extends AbstractController
{
    use DefaultActionTrait;
    use FieldComponentTrait;


    public function __construct(private readonly TranslatorInterface $translator, private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveAction]
    public function setPropertyValue(): void
    {
        $entityData = $this->getEntityData();

        if (!$entityData) {
            return;
        }

        PropertyAccess::createPropertyAccessor()->setValue($entityData, $this->propertyName, $this->fieldValue);

        $this->manager()->flush();


        $this->addFlash(Flash::SUCCESS->value, $this->translator->trans('message.item_updated', [], 'admin'));
    }

    public function manager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}

