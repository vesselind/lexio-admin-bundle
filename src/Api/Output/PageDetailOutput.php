<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Lexio\AdminBundle\Api\MapTransformer\PageContentItemsTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class PageDetailOutput extends PageOutput
{
    /** @var list<ContentItemOutput> */
    #[Groups(['api:view'])]
    #[SerializedName('content_items')]
    #[Map(source: 'contentItems', transform: PageContentItemsTransformer::class)]
    public array $contentItems = [];
}
