<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class TranslationEntryOutput
{
    public function __construct(
        #[Groups(['api:list', 'api:view'])]
        public string $domain,
        #[Groups(['api:list', 'api:view'])]
        public string $locale,
        #[Groups(['api:list', 'api:view'])]
        public string $key,
        #[Groups(['api:list', 'api:view'])]
        public string $value,
    ) {
    }
}
