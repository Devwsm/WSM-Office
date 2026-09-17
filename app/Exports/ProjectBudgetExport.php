<?php

namespace App\Exports;

use App\Models\ProjectBudget;
use Illuminate\Support\Collection;

/**
 * ProjectBudgetExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'budget' (lihat App\Support\ExportImport\ExportCatalog)
 */
class ProjectBudgetExport extends BaseExport
{
    public function __construct(private readonly ?int $projectId = null) {}

    public function rows(): Collection
    {
        return ProjectBudget::query()
            ->with('project')
            ->when($this->projectId, fn($q) => $q->where('project_id', $this->projectId))
            ->orderBy('project_id')
            ->orderBy('category')
            ->get();
    }

    public function headings(): array
    {
        return ['Project', 'Kategori', 'Item', 'Anggaran', 'Realisasi', 'Selisih', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->project?->name ?? '-',
            $row->category,
            $row->item,
            $row->budget,
            $row->actual,
            $row->budget - $row->actual,
            $row->note,
        ];
    }
}