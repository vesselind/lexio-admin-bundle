<?php

namespace Lexio\AdminBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\SortableGroup;
use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Entity\Traits\SortableEntity;
use Lexio\AdminBundle\Entity\Traits\TranslatableEntity;
use Lexio\AdminBundle\Repository\SortableMenuRepository;

#[ORM\Entity(repositoryClass: SortableMenuRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
class SortableMenu
{
    use TimestampableEntity;
    use SortableEntity;
    use TranslatableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Translatable]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Translatable]
    private ?string $path = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[SortableGroup]
    private ?self $parent = null;

    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['positionIndex' => 'asc'])]
    private Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChildren(self $children): self
    {
        if (!$this->children->contains($children)) {
            $this->children->add($children);
            $children->setParent($this);
        }

        return $this;
    }

    public function removeChildren(self $children): self
    {
        if ($this->children->removeElement($children)) {
            // set the owning side to null (unless already changed)
            if ($children->getParent() === $this) {
                $children->setParent(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? 'n/a';
    }
}
