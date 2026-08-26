<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Utils;

use Doctrine\Persistence\ObjectManager;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;

trait ImageLoaderTrait
{
    abstract protected function manager(): ObjectManager;

    /** @return class-string<ImageEntityInterface> */
    abstract protected function getImageEntityFqcn(): string;

    public function getImagePath(string $imageName): string
    {
        $image = $this->manager()
            ->getRepository($this->getImageEntityFqcn())
            ->findOneBy(['originalName' => $imageName]);

        if (!$image instanceof ImageEntityInterface) {
            throw new \RuntimeException('Image with name ' . $imageName . ' not found by image loader.');
        }

        return $image->getFilePath();
    }
}
