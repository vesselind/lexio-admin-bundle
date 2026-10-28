<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Query;

use Lexio\AdminBundle\Contract\Api\ApiQueryInterface;

use Lexio\AdminBundle\Filter\BaseFilter;
use Symfony\Component\Validator\Constraints as Assert;

final class TranslationEntryListQuery extends BaseFilter implements ApiQueryInterface
{
    use ApiQueryFields;

    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:_[a-z0-9]+)*$/D')]
    #[Assert\Length(max: 255)]
    public ?string $domain = null;

    #[Assert\Length(max: 255)]
    public ?string $key = null;

    #[Assert\Length(max: 100000)]
    public ?string $value = null;

    /** @return list<string> */
    public static function sortableFields(): array
    {
        return ['domain', 'key', 'value'];
    }
}
