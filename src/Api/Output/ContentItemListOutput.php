<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Lexio\AdminBundle\Page\ContentItemTypes;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ContentItemListOutput
{
    #[Groups(['api:list'])]
    public ?int $id = null;

    #[Groups(['api:list'])]
    public ?string $name = null;

    #[Groups(['api:list'])]
    public ?ContentItemTypes $type = null;

    #[Groups(['api:list'])]
    #[SerializedName('page_id')]
    #[Map(source: 'page.id')]
    public ?int $pageId = null;

    #[Groups(['api:list'])]
    #[SerializedName('page_name')]
    #[Map(source: 'page.name')]
    public ?string $pageName = null;
}
