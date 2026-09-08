<?php

namespace Lexio\AdminBundle\Entity\Traits;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;

trait HasSeoData
{
    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $metaDescription = null;

    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $ogTitle = null;

    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $ogDescription = null;

    #[ManyToOne(targetEntity: ImageEntityInterface::class)]
    #[JoinColumn(name: 'og_image_id', nullable: true, onDelete: 'RESTRICT')]
    private ?ImageEntityInterface $ogImage = null;


    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getOgTitle(): ?string
    {
        return $this->ogTitle;
    }

    public function setOgTitle(?string $ogTitle): self
    {
        $this->ogTitle = $ogTitle;

        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgDescription(?string $ogDescription): self
    {
        $this->ogDescription = $ogDescription;

        return $this;
    }

    public function getOgImage(): ?ImageEntityInterface
    {
        return $this->ogImage;
    }

    public function setOgImage(?ImageEntityInterface $ogImage): self
    {
        $this->ogImage = $ogImage;

        return $this;
    }

    public function getOgImagePath(): ?string
    {
        return $this->ogImage?->getFilePath();
    }

}
