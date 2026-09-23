<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Form;

use Lexio\AdminBundle\Entity\MailTemplate;
use Lexio\AdminBundle\Form\MailTemplateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Test\TypeTestCase;

final class MailTemplateTypeTest extends TypeTestCase
{
    public function test_enabled_checkbox_is_enabled_by_default_and_has_help_text(): void
    {
        $form = $this->factory->create(
            MailTemplateType::class,
            (new MailTemplate())->setSubject('Subject')->setContent('Body'),
        );

        self::assertTrue($form->has('enabled'));
        self::assertInstanceOf(CheckboxType::class, $form->get('enabled')->getConfig()->getType()->getInnerType());
        self::assertTrue($form->get('enabled')->getData());
        self::assertSame('help.mail_template_enabled', $form->get('enabled')->getConfig()->getOption('help'));
    }
}
