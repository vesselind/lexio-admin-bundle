<?php

namespace Lexio\AdminBundle\Contract\Notification;


use Lexio\AdminBundle\Enum\NotificationLevel;

interface NotificationEntityInterface
{

    public function getId(): ?int;

    public function getContent(): ?string;

    public function setContent(string $content): static;

    public function getLevel(): ?NotificationLevel;

    public function setLevel(NotificationLevel $level): static;

    public function getViewedAt(): ?\DateTimeImmutable;

    public function setViewedAt(?\DateTimeImmutable $viewedAt): static;

    public function isRead(): bool;
    public function markAsRead(): static;

    public function getUser(): ?NotificationUserInterface;

    public function setUser(?NotificationUserInterface $user): static;

    public function getTitle(): ?string;

    public function setTitle(string $title): static;

}