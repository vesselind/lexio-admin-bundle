<?php

namespace Lexio\AdminBundle\Filter;


class FileFilter extends \Lexio\AdminBundle\Filter\BaseFilter
{
    public ?string $name = null;
    public ?string $mime = null;
    public ?int $size = null;
    public ?string $originalName = null;
}
