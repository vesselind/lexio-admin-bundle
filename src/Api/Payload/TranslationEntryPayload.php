<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Payload;

use Lexio\AdminBundle\Contract\Api\ApiPayloadInterface;

use Symfony\Component\Validator\Constraints as Assert;

final class TranslationEntryPayload implements ApiPayloadInterface
{
    use LocaleAwareTrait;

    #[Assert\NotNull]
    #[Assert\Length(max: 100000)]
    public string $value;
}
