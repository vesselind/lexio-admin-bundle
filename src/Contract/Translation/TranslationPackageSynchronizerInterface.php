<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Contract\Translation;

interface TranslationPackageSynchronizerInterface
{
    public function isEnabled(): bool;

    public function canUseAdminActions(): bool;

    public function upload(): TranslationPackageMergeResult;

    public function download(): TranslationPackageMergeResult;
}
