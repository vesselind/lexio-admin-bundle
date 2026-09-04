<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Lexio\AdminBundle\Enum\EmailDeliveryStatus;
use Lexio\AdminBundle\Repository\EmailDeliveryRepository;

#[ORM\Entity(repositoryClass: EmailDeliveryRepository::class)]
class EmailDelivery
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $recipientEmail = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\ManyToOne(inversedBy: 'emailDeliveries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MailTemplate $mailTemplate = null;

    #[ORM\Column(length: 255)]
    private ?string $senderEmail = null;

    #[ORM\Column(length: 32, enumType: EmailDeliveryStatus::class)]
    private EmailDeliveryStatus $status = EmailDeliveryStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getStatus(): EmailDeliveryStatus
    {
        return $this->status;
    }

    public function markSuccessful(\DateTimeImmutable $sentAt): static
    {
        $this->status = EmailDeliveryStatus::SUCCESSFUL;
        $this->sentAt = $sentAt;
        $this->error = null;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function markFailed(string $error): static
    {
        $this->status = EmailDeliveryStatus::FAILED;
        $this->sentAt = null;
        $this->error = $error;

        return $this;
    }

    public function getMailTemplate(): ?MailTemplate
    {
        return $this->mailTemplate;
    }

    public function setMailTemplate(?MailTemplate $mailTemplate): static
    {
        $this->mailTemplate = $mailTemplate;

        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(string $senderEmail): static
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }
}
