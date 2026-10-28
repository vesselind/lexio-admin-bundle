<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\MapTransformer;

use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\File\FileManager;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<object, object>
 */
final class FileUrlTransformer implements TransformCallableInterface
{
    public function __construct(private readonly FileManager $fileManager)
    {
    }

    public function __invoke(mixed $value, object $source, ?object $target): string
    {
        if (!$source instanceof FileEntityInterface) {
            throw new \RuntimeException(sprintf('FileUrlTransformer can only transform file entities, got %s', get_debug_type($source)));
        }

        return $this->fileManager->getAbsoluteUrl($source);
    }
}
