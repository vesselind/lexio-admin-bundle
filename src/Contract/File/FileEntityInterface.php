<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\File;

use Lexio\AdminBundle\Enum\FileAccessType;

/**
 * Contract for file entities managed by the bundle.
 */
interface FileEntityInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(string $name): static;

    public function getMime(): ?string;

    public function setMime(?string $mime): static;

    public function getSize(): ?int;

    public function setSize(?int $size): static;

    public function getOriginalName(): ?string;

    public function setOriginalName(?string $originalName): static;

    public function getDirectory(): string;

    public function getFilePath(): string;

    /** @return array{icon: string, color: string} */
    public function iconColors(): array;

    public function accessType(): FileAccessType;
}

