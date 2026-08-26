<?php

namespace Lexio\AdminBundle\Page;

use Lexio\AdminBundle\Attributes\FieldType;

class BasePage
{
    protected ?int $id = null;

    #[FieldType(ContentItemTypes::TEXT_INPUT)]
    protected ?string $title = null;

    #[FieldType(ContentItemTypes::TEXTAREA)]
    protected ?string $seoDescription = null;

    #[FieldType(ContentItemTypes::TEXTAREA)]
    protected ?string $seoOgTitle = null;

    #[FieldType(ContentItemTypes::TEXTAREA)]
    protected ?string $seoOgDescription = null;

    #[FieldType(ContentItemTypes::IMAGE_PATH)]
    protected ?string $seoOgImage = null;

    #[FieldType(ContentItemTypes::IMAGE_PATH)]
    protected ?string $coverImage = null;

    /**
     * Page Entity ID
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Page Entity ID
     * @param int|null $id
     */
    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    public function getSeoOgTitle(): ?string
    {
        return $this->seoOgTitle;
    }

    public function setSeoOgTitle(?string $seoOgTitle): static
    {
        $this->seoOgTitle = $seoOgTitle;

        return $this;
    }

    public function getSeoOgDescription(): ?string
    {
        return $this->seoOgDescription;
    }

    public function setSeoOgDescription(?string $seoOgDescription): static
    {
        $this->seoOgDescription = $seoOgDescription;

        return $this;
    }

    public function getSeoOgImage(): ?string
    {
        return $this->seoOgImage;
    }

    public function setSeoOgImage(?string $seoOgImage): static
    {
        $this->seoOgImage = $seoOgImage;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }
}
