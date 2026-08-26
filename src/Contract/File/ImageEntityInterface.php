<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\File;

/**
 * Contract for image entities managed by the bundle.
 */
interface ImageEntityInterface extends FileEntityInterface
{
    public function getAlt(): ?string;

    public function setAlt(?string $alt): static;

    public function getRelativePath(): string;
}

