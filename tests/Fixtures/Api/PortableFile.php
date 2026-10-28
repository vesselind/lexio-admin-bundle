<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures\Api;

use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Enum\FileAccessType;

class PortableFile implements FileEntityInterface
{
    public function __construct(private readonly int $id = 42, private string $name = 'fixture.pdf')
    {
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getMime(): ?string { return 'application/pdf'; }
    public function setMime(?string $mime): static { return $this; }
    public function getSize(): ?int { return 100; }
    public function setSize(?int $size): static { return $this; }
    public function getOriginalName(): ?string { return $this->name; }
    public function setOriginalName(?string $originalName): static { return $this; }
    public function getDirectory(): string { return '/uploads/portable'; }
    public function getFilePath(): string { return $this->getDirectory() . '/' . $this->name; }
    public function getCreatedAt(): \DateTimeImmutable { return new \DateTimeImmutable('2025-01-01'); }
    public function iconColors(): array { return ['icon' => 'file', 'color' => 'info']; }
    public function accessType(): FileAccessType { return FileAccessType::PUBLIC; }
}
