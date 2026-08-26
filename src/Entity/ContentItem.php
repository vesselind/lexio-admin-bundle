<?php

namespace Lexio\AdminBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Entity\Traits\TranslatableEntity;
use Lexio\AdminBundle\Page\ContentItemTypes;
use Lexio\AdminBundle\Repository\ContentItemRepository;

#[ORM\Entity(repositoryClass: ContentItemRepository::class)]
class ContentItem
{
    use TimestampableEntity;
    use TranslatableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    protected ?string $name = null;

    #[Translatable]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $value = null;

    #[ORM\Column(length: 255, enumType: ContentItemTypes::class)]
    protected ?ContentItemTypes $type = null;

    #[ORM\ManyToOne(inversedBy: 'contentItems')]
    #[ORM\JoinColumn(nullable: false)]
    protected ?Page $page = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): ?ContentItemTypes
    {
        return $this->type;
    }

    public function setType(ContentItemTypes $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(?Page $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
