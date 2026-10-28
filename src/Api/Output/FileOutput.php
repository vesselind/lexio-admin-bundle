<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Lexio\AdminBundle\Api\MapTransformer\FileUrlTransformer;
use Symfony\Component\ObjectMapper\Attribute\Map;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

class FileOutput
{
    #[Groups(['api:view', 'api:list'])]
    public ?int $id = null;

    #[Groups(['api:view', 'api:list'])]
    public ?string $name = null;

    #[Groups(['api:view', 'api:list'])]
    #[SerializedName('original_name')]
    public ?string $originalName = null;

    #[Groups(['api:view', 'api:list'])]
    public ?string $mime = null;

    #[Groups(['api:view', 'api:list'])]
    public ?int $size = null;

    #[Groups(['api:view', 'api:list'])]
    #[Map(source: 'filePath', transform: FileUrlTransformer::class)]
    public ?string $url = null;

    #[Groups(['api:view', 'api:list'])]
    #[SerializedName('created_at')]
    #[Map(source: 'createdAt', transform: [self::class, 'formatDate'])]
    public ?string $createdAt = null;

    public static function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
