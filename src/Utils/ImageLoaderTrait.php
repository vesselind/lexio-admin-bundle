<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Utils;

use Doctrine\Persistence\ObjectManager;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;

trait ImageLoaderTrait
{
    abstract protected function manager(): ObjectManager;

    /** @return class-string<FileEntityInterface> */
    abstract protected function getImageEntityFqcn(): string;

    public function getImagePath(string $imageName): string
    {
        $image = $this->manager()
            ->getRepository($this->getImageEntityFqcn())
            ->findOneBy(['originalName' => $imageName]);

        if (!$image instanceof FileEntityInterface) {
            throw new \RuntimeException('Image with name ' . $imageName . ' not found by image loader.');
        }

        return $image->getFilePath();
    }
}
