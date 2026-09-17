<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * TemplateExport
 * ---------------------------------------------------------------------
 * Batch 0 (fondasi Export & Import) — generator file template Excel
 * KOSONG buat tombol "Download Template" di semua modul import (Batch
 * 3). Generik, gak perlu bikin class baru per modul — tinggal:
 *
 *   new TemplateExport($workItemImport->templateHeadings(), [
 *       ['Contoh: Finalisasi artwork cover', '2026-10-01', 'aldora@wsm.local', 'High'],
 *   ])
 *
 * Baris contoh SIFATNYA OPSIONAL (boleh array kosong `[]`) — kalau
 * diisi, ditulis dengan style italic abu-abu biar keliatan jelas itu
 * "contoh", bukan data beneran yang harus dihapus manual sama user
 * sebelum isi filenya.
 * ---------------------------------------------------------------------
 */
class TemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $exampleRows
     */
    public function __construct(
        private readonly array $headings,
        private readonly array $exampleRows = [],
    ) {}

    public function array(): array
    {
        return $this->exampleRows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => ['font' => ['bold' => true]],
        ];

        if (! empty($this->exampleRows)) {
            $lastRow = count($this->exampleRows) + 1;
            $styles["2:{$lastRow}"] = ['font' => ['italic' => true, 'color' => ['rgb' => '9A9384']]];
        }

        return $styles;
    }
}