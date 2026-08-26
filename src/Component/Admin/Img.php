<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Img', template: '@LexioAdmin/components/Img.html.twig')]
final class Img
{

}

