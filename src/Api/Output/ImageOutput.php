<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final class ImageOutput extends FileOutput
{
    #[Groups(['api:view', 'api:list'])]
    public ?string $alt = null;
}
