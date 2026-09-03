<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

use Symfony\Component\Translation\TranslatableMessage;

final class DeploymentExecutionException extends \RuntimeException
{
    public function __construct(
        private readonly TranslatableMessage $translatableMessage,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($translatableMessage->getMessage(), 0, $previous);
    }

    public function getTranslatableMessage(): TranslatableMessage
    {
        return $this->translatableMessage;
    }
}
