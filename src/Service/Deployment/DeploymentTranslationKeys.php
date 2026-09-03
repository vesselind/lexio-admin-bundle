<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service\Deployment;

final class DeploymentTranslationKeys
{
    public const string DISABLED = 'deployment.error.disabled';
    public const string REQUIRED_VALUE = 'deployment.error.required_value';
    public const string INVALID_VALUE = 'deployment.error.invalid_value';
    public const string PROCESS_START_FAILED = 'deployment.error.process_start_failed';
    public const string PROCESS_TIMEOUT = 'deployment.error.process_timeout';
    public const string PROCESS_FAILED = 'deployment.error.process_failed';
    public const string DESCRIPTION = 'deployment.command.description';
    public const string TITLE = 'deployment.title';
    public const string EXECUTING = 'deployment.executing';
    public const string COMPLETED = 'deployment.completed';
    public const string FAILED = 'deployment.failed';
}
