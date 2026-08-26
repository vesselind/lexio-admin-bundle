<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Controller\Admin;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTranslationInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z0-9]+(?:_[a-z0-9]+)*$/D')]
        public string $domain,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9]+(?:[_-][A-Za-z0-9]+)*$/D')]
        public string $locale,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*$/D')]
        public string $key,
        #[Assert\Length(max: 100000)]
        public string $value,
    ) {
    }
}
