<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Response;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class PaginatedResponse
{
    /**
     * @param list<object> $data
     */
    public function __construct(

        #[Groups(['api:list'])]
        public array $data,

        #[Groups(['api:list'])]
        public int $page,

        #[Groups(['api:list'])]
        public int $limit,

        #[Groups(['api:list'])]
        public int $total,
    ) {
    }
}
