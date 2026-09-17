<?php

namespace App\Exports;

use App\Models\JobApplication;
use Illuminate\Support\Collection;

/**
 * JobApplicationExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'recruitment-applicants' (lihat App\Support\ExportImport\ExportCatalog)
 */
class JobApplicationExport extends BaseExport
{
    public function __construct(private readonly ?int $jobOpeningId = null) {}

    public function rows(): Collection
    {
        return JobApplication::query()
            ->with('jobOpening')
            ->when($this->jobOpeningId, fn($q) => $q->where('job_opening_id', $this->jobOpeningId))
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Nama', 'Email', 'Telepon', 'Lowongan', 'Status', 'Pesan'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->email,
            $row->phone,
            $row->jobOpening->title ?? '-',
            $row->statusLabel(),
            $row->message,
        ];
    }
}