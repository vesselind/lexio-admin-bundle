<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Api\Payload;

use Lexio\AdminBundle\Contract\Api\ApiPayloadInterface;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Partial page content update.
 *
 * The `content_items` map contains the page field names (the stored ContentItem
 * names, matching the Page class properties) mapped to their new values for the
 * requested locale. Image fields accept a positive image ID or null.
 */
final class PagePayload implements ApiPayloadInterface
{
    use LocaleAwareTrait;

    /** @var array<string, string|int|null> */
    #[SerializedName('content_items')]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1)]
    public array $contentItems = [];
}
