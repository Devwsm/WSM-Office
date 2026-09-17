<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

/**
 * AuditLogExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'audit-log' (lihat App\Support\ExportImport\ExportCatalog)
 */
class AuditLogExport extends BaseExport
{
    public function __construct(
        private readonly ?string $from = null, // 'Y-m-d'
        private readonly ?string $to = null,   // 'Y-m-d'
    ) {}

    public function rows(): Collection
    {
        return AuditLog::query()
            ->with('actor')
            ->when($this->from, fn($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to, fn($q) => $q->whereDate('created_at', '<=', $this->to))
            ->orderByDesc('created_at')
            ->get();
    }

    public function headings(): array
    {
        return ['Waktu', 'Pelaku', 'Aksi', 'Detail'];
    }

    public function map($row): array
    {
        return [
            $row->created_at->format('d/m/Y H:i'),
            $row->actorName(),
            $row->action,
            $row->detail,
        ];
    }
}