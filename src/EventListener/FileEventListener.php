<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\File\FileManager;

final readonly class FileEventListener
{
    public function __construct(private FileManager $fileManager)
    {
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof FileEntityInterface) {
            return;
        }

        $this->fileManager->delete($entity);
    }
}
