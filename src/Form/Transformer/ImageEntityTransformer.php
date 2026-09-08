<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Form\Transformer;

use Lexio\AdminBundle\Contract\File\FileRepositoryInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Maps a managed image relation to the hidden image ID used by the browser.
 *
 * @implements DataTransformerInterface<ImageEntityInterface|string, mixed>
 */
final readonly class ImageEntityTransformer implements DataTransformerInterface
{
    public function __construct(private FileRepositoryInterface $fileRepository)
    {
    }

    public function transform(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!$value instanceof ImageEntityInterface) {
            throw new TransformationFailedException('Expected an image entity.');
        }

        $id = $value->getId();

        if ($id === null) {
            throw new TransformationFailedException('The selected image must be persisted before it can be selected.');
        }

        return (string) $id;
    }

    public function reverseTransform(mixed $value): ?ImageEntityInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (get_debug_type($value) !== 'string' || !ctype_digit((string) $value)) {
            throw new TransformationFailedException('The image reference must be a numeric image ID.');
        }

        $id = (int) $value;

        if ($id < 1) {
            throw new TransformationFailedException('The image reference must be a positive image ID.');
        }

        $image = $this->fileRepository->findById($id);

        if (!$image instanceof ImageEntityInterface) {
            throw new TransformationFailedException(sprintf('Image with ID %d was not found.', $id));
        }

        return $image;
    }
}
