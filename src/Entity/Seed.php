<?php

namespace Lexio\AdminBundle\Entity;

use Lexio\AdminBundle\Repository\DataLoaderRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Repository\SeedRepository;

#[ORM\Entity(repositoryClass: SeedRepository::class)]
class Seed
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var class-string
     */
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return ?class-string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param class-string $name
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
