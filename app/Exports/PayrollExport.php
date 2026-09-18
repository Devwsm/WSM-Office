<?php

namespace App\Exports;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;

/**
 * PayrollExport — Batch 2.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'payroll', format 'excel' (lihat App\Support\ExportImport\ExportCatalog)
 *
 * Ini CUMA buat rekap payroll SEBULAN SEMUA KARYAWAN (Excel) — padanan
 * `dashboard.payroll.index` tapi bisa didownload. Slip gaji PER-KARYAWAN
 * (PDF) SENGAJA TIDAK lewat class ini: itu 1 dokumen per record, bukan
 * 1 baris di tabel, jadi ditangani langsung di
 * ExportController::downloadPayrollSlip() lewat alur "picker" (lihat
 * catatan di class ExportController & view
 * dashboard/export-import/picker.blade.php).
 */
class PayrollExport extends BaseExport
{
    public function __construct(private readonly string $period) {}

    public function rows(): Collection
    {
        return PayrollRecord::query()
            ->with('user')
            ->where('period', $this->period)
            ->get()
            ->sortBy(fn(PayrollRecord $row) => $row->user->name)
            ->values();
    }

    public function headings(): array
    {
        return ['Karyawan', 'Periode', 'Gaji Pokok', 'Lembur', 'Potongan Kurang Jam', 'Penyesuaian Lain', 'Total', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->user->name,
            $row->periodLabel(),
            $row->base_salary,
            $row->overtime_amount,
            $row->shortage_deduction,
            $row->other_adjustment,
            $row->total,
            $row->statusLabel(),
        ];
    }
}