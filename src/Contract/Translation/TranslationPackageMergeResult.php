<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Translation;

final readonly class TranslationPackageMergeResult
{
    public function __construct(
        public int $filesCreated,
        public int $filesUpdated,
        public int $keysInserted,
        public int $keysUpdated,
        public int $keysUnchanged,
    ) {
    }
}
