<?php

namespace App\Exports;

use App\Models\RoyaltyEntry;
use Illuminate\Support\Collection;

/**
 * RoyaltyEntryExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'royalty' (lihat App\Support\ExportImport\ExportCatalog)
 */
class RoyaltyEntryExport extends BaseExport
{
    public function __construct(private readonly ?string $period = null) {}

    public function rows(): Collection
    {
        return RoyaltyEntry::query()
            ->when($this->period, fn($q) => $q->where('period', $this->period))
            ->orderByDesc('period')
            ->get();
    }

    public function headings(): array
    {
        return ['Judul', 'Periode', 'Sumber', 'Status', 'Gross', 'Share (%)', 'Recoup', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->title,
            $row->period,
            $row->source,
            $row->status,
            $row->gross,
            $row->share_pct,
            $row->recoup,
            $row->note,
        ];
    }
}