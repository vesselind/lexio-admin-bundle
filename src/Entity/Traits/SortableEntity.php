<?php

namespace Lexio\AdminBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait SortableEntity
{
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: 'integer')]
    protected ?int $positionIndex;

    public function getPositionIndex(): ?int
    {
        return $this->positionIndex;
    }

    public function setPositionIndex(int $positionIndex): self
    {
        $this->positionIndex = $positionIndex;

        return $this;
    }
}
