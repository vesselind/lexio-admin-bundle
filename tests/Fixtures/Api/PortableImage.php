<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures\Api;

use Lexio\AdminBundle\Contract\File\ImageEntityInterface;

final class PortableImage extends PortableFile implements ImageEntityInterface
{
    public function getAlt(): ?string { return 'Portable image'; }
    public function setAlt(?string $alt): static { return $this; }
    public function getRelativePath(): string { return '/images/' . $this->getName(); }
}
