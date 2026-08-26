<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class MakeAdminSectionCommandTemplateTest extends TestCase
{
    public function test_crud_controller_template_uses_the_command_kebab_case_variable(): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../../templates'),
            ['strict_variables' => true],
        );

        $rendered = $twig->render('maker/crud_controller.html.twig', [
            'columnName' => null,
            'shortClassName' => 'Invoice',
            'shortClassNameLowercase' => 'invoice',
            'snakeCaseClassName' => 'invoice',
            'kebabCaseClassName' => 'invoice',
        ]);

        self::assertStringContainsString("#[Route('/admin/invoice')]", $rendered);
    }

    public function test_crud_controller_template_imports_bundle_crud_dependencies(): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../../templates'),
            ['strict_variables' => true],
        );

        $rendered = $twig->render('maker/crud_controller.html.twig', [
            'columnName' => null,
            'shortClassName' => 'Invoice',
            'shortClassNameLowercase' => 'invoice',
            'snakeCaseClassName' => 'invoice',
            'kebabCaseClassName' => 'invoice',
        ]);

        foreach ([
            'use Lexio\\AdminBundle\\AdminCore\\Bulk\\BulkAction;',
            'use Lexio\\AdminBundle\\AdminCore\\Bulk\\BulkContext;',
            'use Lexio\\AdminBundle\\AdminCore\\FormContext;',
            'use Lexio\\AdminBundle\\AdminCore\\Fields\\DateTimeField;',
            'use Lexio\\AdminBundle\\AdminCore\\Fields\\DropdownActionsField;',
            'use Lexio\\AdminBundle\\AdminCore\\Fields\\IdField;',
            'use Lexio\\AdminBundle\\AdminCore\\Fields\\TitleField;',
            'use Lexio\\AdminBundle\\AdminCore\\Action\\DeleteAction;',
            'use Lexio\\AdminBundle\\AdminCore\\Action\\UpdateAction;',
            'use Lexio\\AdminBundle\\AdminCore\\Listing\\ListingContext;',
            'use Lexio\\AdminBundle\\Controller\\BaseCrudController;',
        ] as $import) {
            self::assertStringContainsString($import, $rendered);
        }

        self::assertStringNotContainsString('use App\\AdminCore\\', $rendered);
    }

    public function test_admin_test_template_uses_the_command_kebab_case_variable(): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../../templates'),
            ['strict_variables' => true],
        );

        $rendered = $twig->render('maker/admin_test.html.twig', [
            'shortClassName' => 'Invoice',
            'snakeCaseClassName' => 'invoice',
            'kebabCaseClassName' => 'invoice',
            'filterFields' => [],
            'firstField' => null,
        ]);

        self::assertStringContainsString("->visit('/admin/invoice')", $rendered);
    }

    public function test_filter_template_imports_the_bundle_base_filter(): void
    {
        $twig = new Environment(
            new FilesystemLoader(__DIR__ . '/../../../templates'),
            ['strict_variables' => true],
        );

        $rendered = $twig->render('maker/filter.html.twig', [
            'shortClassName' => 'Invoice',
            'fields' => ['public ?string $number = null;'],
        ]);

        self::assertStringContainsString('use Lexio\\AdminBundle\\Filter\\BaseFilter;', $rendered);
        self::assertStringContainsString('class InvoiceFilter extends BaseFilter', $rendered);
    }
}
