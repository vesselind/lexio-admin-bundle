<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:BaseModalBody', template: '@LexioAdmin/components/Admin/BaseModalBody.html.twig')]
final class BaseModalBody
{
}

