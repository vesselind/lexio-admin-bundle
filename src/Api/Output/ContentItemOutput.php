<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Lexio\AdminBundle\Page\ContentItemTypes;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

final class ContentItemOutput
{
    #[Groups(['api:view'])]
    public ?int $id = null;

    #[Groups(['api:view'])]
    public ?string $name = null;

    #[Groups(['api:view'])]
    public ?ContentItemTypes $type = null;

    #[Groups(['api:view'])]
    public ?string $value = null;

    #[Groups(['api:view'])]
    #[SerializedName('image_id')]
    public ?int $imageId = null;
}
