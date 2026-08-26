<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Translation;

use Lexio\AdminBundle\Contract\Translation\TranslationPackageMergeResult;
use Lexio\AdminBundle\Contract\Translation\TranslationPackageSynchronizerInterface;
use Lexio\AdminBundle\Contract\Translation\TranslationSynchronizationException;

final readonly class TranslationPackageSynchronizer implements TranslationPackageSynchronizerInterface
{
    public function __construct(
        private TranslationSynchronizationOptions $options,
        private TranslationPackageManager $packageManager,
        private TranslationPackageHttpClient $httpClient,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->options->isEnabled();
    }

    public function canUseAdminActions(): bool
    {
        return $this->options->areAdminActionsAllowed();
    }

    public function upload(): TranslationPackageMergeResult
    {
        $this->assertEnabled();

        return $this->httpClient->upload($this->packageManager->export());
    }

    public function download(): TranslationPackageMergeResult
    {
        $this->assertEnabled();

        return $this->packageManager->import($this->httpClient->download());
    }

    private function assertEnabled(): void
    {
        if (!$this->options->isEnabled()) {
            throw new TranslationSynchronizationException('Translation synchronization is disabled.');
        }
    }
}
