<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Repository\MailTemplateRepository;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: MailTemplateRepository::class)]
class MailTemplate
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 655)]
    private ?string $subject = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    /** @var array<string> */
    #[ORM\Column]
    private array $placeholders = [];

    /** @var Collection<int, EmailDelivery> */
    #[ORM\OneToMany(targetEntity: EmailDelivery::class, mappedBy: 'mailTemplate', orphanRemoval: true)]
    private Collection $emailDeliveries;

    public function __construct()
    {
        $this->emailDeliveries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSubject(): string
    {
        Assert::notNull($this->subject, 'Mail subject cannot be null');

        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /** @return string[] */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /** @param string[] $placeholders */
    public function setPlaceholders(array $placeholders): static
    {
        $this->placeholders = $placeholders;

        return $this;
    }

    /** @return Collection<int, EmailDelivery> */
    public function getEmailDeliveries(): Collection
    {
        return $this->emailDeliveries;
    }

    public function addEmailDelivery(EmailDelivery $emailDelivery): static
    {
        if (!$this->emailDeliveries->contains($emailDelivery)) {
            $this->emailDeliveries->add($emailDelivery);
            $emailDelivery->setMailTemplate($this);
        }

        return $this;
    }

    public function removeEmailDelivery(EmailDelivery $emailDelivery): static
    {
        if ($this->emailDeliveries->removeElement($emailDelivery) && $emailDelivery->getMailTemplate() === $this) {
            $emailDelivery->setMailTemplate(null);
        }

        return $this;
    }
}
