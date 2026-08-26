<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\File;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Lazy singleton wrapper around Intervention Image's ImageManager (GD driver).
 */
final class InterventionImageManager
{
    private ?ImageManager $instance = null;

    public function getInstance(): ImageManager
    {
        if ($this->instance === null) {
            $this->instance = new ImageManager(Driver::class);
        }

        return $this->instance;
    }
}

