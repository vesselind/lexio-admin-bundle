<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form\Transformer;

use Lexio\AdminBundle\Contract\File\FileRepositoryInterface;
use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Form\Transformer\ImageEntityTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class ImageEntityTransformerTest extends TestCase
{
    public function test_it_maps_a_managed_image_to_an_id_and_back(): void
    {
        $image = $this->createStub(ImageEntityInterface::class);
        $image->method('getId')->willReturn(42);

        $repository = $this->createMock(FileRepositoryInterface::class);
        $repository->expects(self::once())->method('findById')->with(42)->willReturn($image);

        $transformer = new ImageEntityTransformer($repository);

        self::assertSame('42', $transformer->transform($image));
        self::assertSame($image, $transformer->reverseTransform('42'));
    }

    public function test_it_rejects_unknown_or_non_numeric_image_references(): void
    {
        $repository = $this->createMock(FileRepositoryInterface::class);
        $repository->expects(self::once())->method('findById')->with(42)->willReturn(null);
        $transformer = new ImageEntityTransformer($repository);

        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform('42');
    }

    public function test_it_maps_empty_form_values_to_no_relation(): void
    {
        $repository = $this->createStub(FileRepositoryInterface::class);
        $transformer = new ImageEntityTransformer($repository);

        self::assertNull($transformer->transform(null));
        self::assertNull($transformer->reverseTransform(''));
    }
}
