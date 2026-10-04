<?php

namespace App\Exports;

use App\Models\BudgetCategory;
use App\Models\ProjectBudget;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * ProjectBudgetExport — Batch 1.
 * 2026-10-04: baris di kategori terbatas yang tidak boleh dilihat $viewer
 * tidak ikut diekspor (data anggaran sensitif — lihat BudgetCategory).
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'budget' (lihat App\Support\ExportImport\ExportCatalog)
 */
class ProjectBudgetExport extends BaseExport
{
    public function __construct(private readonly ?int $projectId = null, private readonly ?User $viewer = null) {}

    public function rows(): Collection
    {
        $hidden = $this->viewer ? BudgetCategory::hiddenKeysFor($this->viewer) : [];

        return ProjectBudget::query()
            ->with('project')
            ->when($this->projectId, fn($q) => $q->where('project_id', $this->projectId))
            ->orderBy('project_id')
            ->orderBy('category')
            ->get()
            ->reject(fn(ProjectBudget $l) => isset($hidden[BudgetCategory::key($l->project_id, $l->category)]))
            ->values();
    }

    public function headings(): array
    {
        return ['Project', 'Kategori', 'Item', 'Lagu', 'Anggaran', 'Realisasi', 'Selisih', 'Bukti Bayar', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->project?->name ?? '-',
            $row->category,
            $row->item,
            $row->song_title,
            $row->budget,
            $row->actual,
            $row->budget - $row->actual,
            $row->proof_link,
            $row->note,
        ];
    }
}