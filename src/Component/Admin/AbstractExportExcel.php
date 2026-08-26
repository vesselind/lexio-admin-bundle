<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Component\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Abstract Excel export live component.
 *
 * Extend this in your application for each listing that needs an export button.
 * You must implement:
 *   - getColumns(): string[]  — list of column identifiers to show as checkboxes
 *   - doExport(string[] $columns): Response  — generate and return the Excel file
 *
 * Example:
 *
 *   #[AsLiveComponent('Admin:ExportPostsExcel')]
 *   class ExportPostsExcel extends AbstractExportExcel
 *   {
 *       public function getColumns(): array { return ['title', 'author', 'published_at']; }
 *       protected function doExport(array $columns): Response { ... }
 *   }
 */
#[AsLiveComponent(name: 'Admin:ExportExcel', template: '@LexioAdmin/components/Admin/ExportExcel.html.twig')]
abstract class AbstractExportExcel
{
    use DefaultActionTrait;

    /**
     * Column identifiers displayed as checkboxes.
     * Translation key: 'column.<identifier>' in the 'admin' domain.
     *
     * @return string[]
     */
    abstract public function getColumns(): array;

    /**
     * Generate and stream the Excel file for the selected columns.
     *
     * @param string[] $columns
     */
    abstract protected function doExport(array $columns): Response;

    #[LiveAction]
    public function export(Request $request): Response
    {
        /** @var string[] $columns */
        $columns = $request->request->all()['columns'] ?? [];

        return $this->doExport($columns);
    }
}

