<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:BaseModal', template: '@LexioAdmin/components/Admin/BaseModal.html.twig')]
final class BaseModal
{
    public string $modalSize = '';
}

