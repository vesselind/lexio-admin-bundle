<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\File;

interface FileRepositoryInterface
{
    public function searchByPath(string $path): ?FileEntityInterface;
}
