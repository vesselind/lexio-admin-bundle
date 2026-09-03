<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

/**
 * Renders the visual part of the image selector form control.
 *
 * The form theme supplies the original form widget through the input block;
 * the component owns the reusable card/empty/thumbnail presentation.
 */
final class InputImageSelector
{
    public ?string $value = null;
    public ?string $fileName = null;
    public string $galleryUrl = '';
    public string $galleryTitle = '';
    public string $buttonLabel = '';
    public bool $disabled = false;
}
