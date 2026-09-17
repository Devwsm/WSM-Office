<?php

namespace App\Exports;

use App\Models\EmployeeContract;
use Illuminate\Support\Collection;

/**
 * EmployeeContractExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'contracts' (lihat App\Support\ExportImport\ExportCatalog)
 */
class EmployeeContractExport extends BaseExport
{
    public function rows(): Collection
    {
        return EmployeeContract::query()
            ->with('employee')
            ->orderBy('end_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Karyawan', 'Mulai', 'Berakhir', 'Segera Berakhir?', 'File', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            optional($row->start_date)->format('d/m/Y'),
            optional($row->end_date)->format('d/m/Y'),
            $row->isExpiringSoon() ? 'Ya' : 'Tidak',
            $row->original_filename,
            $row->notes,
        ];
    }
}