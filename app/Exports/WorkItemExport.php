<?php

namespace App\Exports;

use App\Models\WorkItem;
use Illuminate\Support\Collection;

/**
 * WorkItemExport — Batch 1.
 * Dipakai controller: App\Http\Controllers\Dashboard\ExportImport\ExportController
 * Catalog key: 'work-tracker' (lihat App\Support\ExportImport\ExportCatalog)
 */
class WorkItemExport extends BaseExport
{
    public function __construct(private readonly ?int $projectId = null) {}

    public function rows(): Collection
    {
        return WorkItem::query()
            ->with(['project', 'pic'])
            ->when($this->projectId, fn($q) => $q->where('project_id', $this->projectId))
            ->orderBy('due_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Project', 'Section', 'Judul', 'Tenggat', 'PIC', 'Progress', 'Prioritas', 'Catatan'];
    }

    public function map($row): array
    {
        return [
            $row->project?->name ?? 'General WSM',
            $row->section,
            $row->title,
            optional($row->due_date)->format('d/m/Y'),
            $row->pic?->name ?? '-',
            $row->progress,
            $row->priority,
            $row->notes,
        ];
    }
}