<?php

namespace Lexio\AdminBundle\Attributes;


use Lexio\AdminBundle\Page\ContentItemTypes;

#[\Attribute]
readonly class FieldType
{

    public function __construct(public ContentItemTypes $contentItemType) {
    }
}
