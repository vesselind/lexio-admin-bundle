<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\MapTransformer;

use Lexio\AdminBundle\Exception\ApiValidationException;
use Lexio\AdminBundle\Contract\File\FileRepositoryInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Symfony\Component\ObjectMapper\TransformCallableInterface;

/**
 * @implements TransformCallableInterface<object, object>
 */
final readonly class ImageMapTransformer implements TransformCallableInterface
{
    public function __construct(private FileRepositoryInterface $fileRepository)
    {
    }

    public function __invoke(mixed $value, object $source, ?object $target): ?ImageEntityInterface
    {
        if ($value === null) {
            return null;
        }

        $image = $this->fileRepository->findById((int) $value);

        if (!$image instanceof ImageEntityInterface) {
            throw ApiValidationException::field('og_image_id', 'The referenced image does not exist.');
        }

        return $image;
    }
}
