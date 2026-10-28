<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Query;

use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;

use Lexio\AdminBundle\Filter\ImageFilter;

final class ImageListQuery extends ImageFilter implements ApiQueryInterface
{
    use ApiQueryFields;
}
