<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * EmployeeExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'employees' (lihat App\Support\ExportImport\ExportCatalog) — owner-only.
 */
class EmployeeExport extends BaseExport
{
    public function rows(): Collection
    {
        return User::query()
            ->with('manager')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return ['Nama', 'Email', 'Role', 'Divisi', 'Jabatan', 'Tanggal Bergabung', 'Atasan'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->email,
            $row->roleLabel(),
            $row->division,
            $row->job_title,
            optional($row->join_date)->format('d/m/Y'),
            $row->manager?->name ?? '-',
        ];
    }
}