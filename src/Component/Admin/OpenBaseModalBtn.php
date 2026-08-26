<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:OpenBaseModalBtn', template: '@LexioAdmin/components/Admin/OpenBaseModalBtn.html.twig')]
final class OpenBaseModalBtn
{
}

