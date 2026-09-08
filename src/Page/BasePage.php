<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Page;

use Lexio\AdminBundle\Attributes\FieldType;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;

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

    #[FieldType(ContentItemTypes::IMAGE)]
    protected ?ImageEntityInterface $seoOgImage = null;

    #[FieldType(ContentItemTypes::IMAGE)]
    protected ?ImageEntityInterface $coverImage = null;

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

    public function getSeoOgImage(): ?ImageEntityInterface
    {
        return $this->seoOgImage;
    }

    public function setSeoOgImage(?ImageEntityInterface $seoOgImage): static
    {
        $this->seoOgImage = $seoOgImage;

        return $this;
    }

    public function getCoverImage(): ?ImageEntityInterface
    {
        return $this->coverImage;
    }

    public function setCoverImage(?ImageEntityInterface $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getSeoOgImagePath(): ?string
    {
        return $this->seoOgImage?->getFilePath();
    }

    public function getCoverImagePath(): ?string
    {
        return $this->coverImage?->getFilePath();
    }
}
