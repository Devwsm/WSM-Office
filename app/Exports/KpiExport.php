<?php

namespace App\Exports;

use App\Models\Kpi;
use Illuminate\Support\Collection;

/**
 * KpiExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'kpi' (lihat App\Support\ExportImport\ExportCatalog)
 */
class KpiExport extends BaseExport
{
    public function __construct(private readonly ?string $period = null) {}

    public function rows(): Collection
    {
        return Kpi::query()
            ->with('employee')
            ->when($this->period, fn($q) => $q->where('period', $this->period))
            ->orderBy('employee_id')
            ->orderByDesc('due_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Karyawan', 'Judul KPI', 'Periode', 'Target', 'Capaian', 'Satuan', 'Bobot (%)', 'Tenggat', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->title,
            $row->period,
            $row->target,
            $row->current,
            $row->unit,
            $row->weight,
            optional($row->due_date)->format('d/m/Y'),
            $row->status,
        ];
    }
}