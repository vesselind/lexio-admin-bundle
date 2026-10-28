<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures\Api;

use Lexio\AdminBundle\Contract\File\ImageEntityInterface;
use Lexio\AdminBundle\Controller\Api\ImageController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bridge/v2/images', requirements: ['id' => '\d+'])]
final class PortableImageController extends ImageController
{
    public function getEntityFqcn(): string { return PortableImage::class; }
    public function imageEntity(): ImageEntityInterface { return new PortableImage(); }
}
