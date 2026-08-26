<?php

namespace Lexio\AdminBundle\Entity\Traits;

use Doctrine\ORM\Mapping\Column;

trait HasSeoData
{
    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $metaDescription;

    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $ogTitle;

    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $ogDescription;

    #[Column(type: 'string', length: 555, nullable: true)]
    private ?string $ogImage;


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

    public function getOgImage(): ?string
    {
        return $this->ogImage;
    }

    public function setOgImage(?string $ogImage): self
    {
        $this->ogImage = $ogImage;

        return $this;
    }

}
