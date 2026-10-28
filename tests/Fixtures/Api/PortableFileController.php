<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Fixtures\Api;

use Lexio\AdminBundle\Contract\File\FileEntityInterface;
use Lexio\AdminBundle\Controller\Api\FileController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bridge/v2/files', requirements: ['id' => '\d+'])]
final class PortableFileController extends FileController
{
    public function getEntityFqcn(): string { return PortableFile::class; }
    public function fileEntity(): FileEntityInterface { return new PortableFile(); }
}
