<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

use Symfony\Component\Translation\TranslatableMessage;

final class DeploymentConfigurationException extends \InvalidArgumentException
{
    public function __construct(
        private readonly TranslatableMessage $translatableMessage,
    ) {
        parent::__construct($translatableMessage->getMessage());
    }

    public function getTranslatableMessage(): TranslatableMessage
    {
        return $this->translatableMessage;
    }
}
