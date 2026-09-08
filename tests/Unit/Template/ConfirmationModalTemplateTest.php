<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Template;

use PHPUnit\Framework\TestCase;

final class ConfirmationModalTemplateTest extends TestCase
{
    public function test_dropdown_confirmation_modals_separate_entity_and_dom_identifiers(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 3) . '/templates/admin/fields/dropdown_actions_field.html.twig'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'subjectId="{{ field.entityInstance.id }}"',
            $template,
        );
        self::assertStringContainsString(
            'modalId="{{ field.entityInstance.id ~ \'_\' ~ action.snakeLabel }}"',
            $template,
        );
    }
}
