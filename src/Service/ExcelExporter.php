<?php

declare(strict_types=1);

namespace Lexio\AdminBundle\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PropertyAccess\PropertyAccess;
use function Symfony\Component\String\u;

class ExcelExporter
{
    private ?string $fileName      = null;
    private string  $creator       = 'Lexio Admin';
    private ?string $lastModifiedBy = 'Lexio Admin';
    private ?string $title         = null;
    private ?string $subject       = null;
    private ?string $description   = null;

    public function __construct(private readonly Filesystem $filesystem)
    {
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function setLastModifiedBy(?string $lastModifiedBy): static
    {
        $this->lastModifiedBy = $lastModifiedBy;

        return $this;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Generates a temporary .xlsx file from the given rows and columns.
     *
     * @param array<mixed>  $rows    Objects / arrays to iterate over.
     * @param string[]      $columns Property paths (e.g. 'title', 'author.name').
     *
     * @return string Path to the generated temp file.
     */
    public function xls(array $rows, array $columns): string
    {
        $spreadsheet = new Spreadsheet();

        if (count($columns) === 0) {
            return (string) tempnam(sys_get_temp_dir(), $this->fileName ?? 'empty_export_');
        }

        $lastLetter    = chr(64 + count($columns));
        $columnIndexes = range('A', $lastLetter);

        $spreadsheet->getProperties()
            ->setCreator($this->creator)
            ->setLastModifiedBy($this->lastModifiedBy ?? '')
            ->setTitle($this->title ?? '')
            ->setSubject($this->subject ?? '')
            ->setDescription($this->description ?? '');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->title ?? 'Export');

        foreach ($columnIndexes as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($rows as $rowIndex => $row) {
            foreach ($columns as $columnIndex => $column) {
                $cell  = $columnIndexes[$columnIndex] . ($rowIndex + 1);
                $value = $accessor->getValue($row, $column);

                $sheet->setCellValue($cell, $value);

                if (is_numeric($value)) {
                    $format = str_contains((string) $value, '.')
                        ? NumberFormat::FORMAT_NUMBER_00
                        : NumberFormat::FORMAT_NUMBER;

                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);
                }

                if (u((string) $value)->containsAny("\n")) {
                    $spreadsheet->getActiveSheet()
                        ->getStyle($cell)
                        ->getAlignment()
                        ->setWrapText(true);
                }
            }
        }

        $writer      = new Xlsx($spreadsheet);
        $tempFilepath = $this->filesystem->tempnam(
            sys_get_temp_dir(),
            $this->fileName ?? 'export_',
            '.xlsx'
        );

        $writer->save($tempFilepath);

        return $tempFilepath;
    }

    /**
     * Compresses multiple files into a temporary ZIP archive.
     *
     * @param array<array{path: string, destination: string}> $files
     */
    public function zipFile(array $files): string
    {
        $tempPath = $this->filesystem->tempnam(
            sys_get_temp_dir(),
            $this->fileName ?? 'export_',
            '.zip'
        );

        $zip = new \ZipArchive();
        $zip->open($tempPath);

        foreach ($files as $file) {
            $zip->addFile($file['path'], $file['destination']);
        }

        $zip->close();

        return $tempPath;
    }
}

