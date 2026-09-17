<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;

/**
 * LeaveRequestExport — Batch 1 ("Rekap Izin/Cuti Tim").
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'leave-recap' (lihat App\Support\ExportImport\ExportCatalog)
 */
class LeaveRequestExport extends BaseExport
{
    public function __construct(private readonly ?string $period = null) {} // format 'Y-m', match ke start_date

    public function rows(): Collection
    {
        return LeaveRequest::query()
            ->with('user')
            ->when(
                $this->period,
                fn($q) => $q->whereRaw("DATE_FORMAT(start_date, '%Y-%m') = ?", [$this->period])
            )
            ->orderBy('start_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Karyawan', 'Jenis', 'Mulai', 'Selesai', 'Jumlah Hari', 'Status', 'Alasan'];
    }

    public function map($row): array
    {
        return [
            $row->user->name,
            $row->typeLabel(),
            $row->start_date->format('d/m/Y'),
            $row->end_date->format('d/m/Y'),
            $row->work_days,
            $row->statusLabel(),
            $row->reason,
        ];
    }
}