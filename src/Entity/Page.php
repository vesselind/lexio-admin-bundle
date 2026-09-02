<?php

namespace Lexio\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Entity\Traits\HasSeoData;
use Lexio\AdminBundle\Entity\Traits\TranslatableEntity;
use Lexio\AdminBundle\Repository\PageRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Index(name: 'name_index', columns: ['name'])]
class Page
{
    use TimestampableEntity;
    use TranslatableEntity;
    use HasSeoData;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Translatable]
    private ?string $title = null;

    /**
     * @var Collection<int, ContentItem>
     */
    #[ORM\OneToMany(targetEntity: ContentItem::class, mappedBy: 'page', orphanRemoval: true)]
    private Collection $contentItems;

    public function __construct()
    {
        $this->contentItems = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Retrieves the FQCN of the page object
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName() ?? '';
    }


    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, ContentItem>
     */
    public function getContentItems(): Collection
    {
        return $this->contentItems;
    }

    public function addContentItem(ContentItem $contentItem): static
    {
        if (!$this->contentItems->contains($contentItem)) {
            $this->contentItems->add($contentItem);
            $contentItem->setPage($this);
        }

        return $this;
    }

    public function removeContentItem(ContentItem $contentItem): static
    {
        if ($this->contentItems->removeElement($contentItem) && $contentItem->getPage() === $this) {
            // set the owning side to null (unless already changed)
            $contentItem->setPage(null);
        }

        return $this;
    }
}
