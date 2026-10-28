<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Query;

use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;

use Lexio\AdminBundle\Filter\FileFilter;

final class FileListQuery extends FileFilter implements ApiQueryInterface
{
    use ApiQueryFields;
}
