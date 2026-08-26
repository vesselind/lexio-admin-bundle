<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Reads an Excel file and returns its data as an associative array.
 *
 * The first row is treated as the header (column names).
 *
 * Example output:
 *   [
 *     ['name' => 'site_title', 'value' => 'My Site'],
 *     ...
 *   ]
 */
class ExcelImporter
{
    private Spreadsheet $spreadsheet;

    public function loadFile(string $filePath): static
    {
        $this->spreadsheet = IOFactory::load($filePath);

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public function getData(): ?array
    {
        $worksheet           = $this->spreadsheet->getActiveSheet();
        $highestRow          = $worksheet->getHighestRow();
        $highestColumn       = $worksheet->getHighestColumn();
        $highestColumnIndex  = Coordinate::columnIndexFromString($highestColumn);

        $data = [];

        for ($row = 1; $row <= $highestRow; ++$row) {
            $rowData = [];

            for ($col = 1; $col <= $highestColumnIndex; ++$col) {
                $rowData[] = $worksheet->getCell([$col, $row])->getValue();
            }

            $data[] = $rowData;
        }

        // Use first row as header keys
        $header = array_shift($data);

        if ($header === null) {
            return null;
        }

        $result = [];

        foreach ($data as $key => $row) {
            foreach ($header as $columnIndex => $fieldName) {
                $result[$key][$fieldName] = $row[$columnIndex] ?? null;
            }
        }

        return $result ?: null;
    }
}

