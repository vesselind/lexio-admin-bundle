<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

class PageOutput
{
    #[Groups(['api:view', 'api:list'])]
    public ?int $id = null;

    #[Groups(['api:view', 'api:list'])]
    public ?string $name = null;

    #[Groups(['api:view', 'api:list'])]
    public ?string $title = null;

    #[Groups(['api:view'])]
    #[SerializedName('created_at')]
    #[Map(source: 'createdAt', transform: [self::class, 'formatDate'])]
    public ?string $createdAt = null;

    #[Groups(['api:view', 'api:list'])]
    #[SerializedName('updated_at')]
    #[Map(source: 'updatedAt', transform: [self::class, 'formatDate'])]
    public ?string $updatedAt = null;

    public static function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
