<?php

namespace Lexio\AdminBundle\Component\Admin;

use Lexio\AdminBundle\AdminCore\FormContext;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'Admin:BaseForm', template: '@LexioAdmin/components/Admin/BaseForm.html.twig')]
class BaseForm
{
    public ?FormView $form = null;

    public ?FormContext $formContext = null;
}