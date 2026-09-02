<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:Img', template: '@LexioAdmin/components/Admin/Img.html.twig')]
final class Img
{

}

