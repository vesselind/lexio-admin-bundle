<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Entity;

use Lexio\AdminBundle\Enum\SecurityEvents;
use Lexio\AdminBundle\Repository\SecurityLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: SecurityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
class SecurityLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, enumType: SecurityEvents::class)]
    private ?SecurityEvents $type = null;

    #[ORM\Column(length: 255)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 600, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actingUser = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $affectedUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?SecurityEvents
    {
        return $this->type;
    }

    public function setType(?SecurityEvents $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getActingUser(): ?string
    {
        return $this->actingUser;
    }

    public function setActingUser(?string $actingUser): static
    {
        $this->actingUser = $actingUser;

        return $this;
    }

    public function getAffectedUser(): ?string
    {
        return $this->affectedUser;
    }

    public function setAffectedUser(?string $affectedUser): static
    {
        $this->affectedUser = $affectedUser;

        return $this;
    }
}
