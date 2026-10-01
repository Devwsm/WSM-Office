<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Sheet "Project" — pasangan Field / Value, isinya sama dengan info project di prototype. */
class ProjectInfoSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(private readonly Project $project, private readonly ?string $exportedBy = null) {}

    public function title(): string
    {
        return 'Project';
    }

    public function array(): array
    {
        $p = $this->project->loadMissing('lead:id,name');

        $total = $p->workItems()->count();
        $done = $p->workItems()->where('progress', 'Done')->count();

        return [
            ['Field', 'Value'],
            ['Project', $p->name],
            ['Start Date', optional($p->start_date)->format('d/m/Y') ?? ''],
            ['End Date', optional($p->end_date)->format('d/m/Y') ?? ''],
            ['Priority', $p->priority],
            ['Status', $p->status],
            ['Visibility', $p->visibilityLabel()],
            ['Project Lead', $p->lead?->name ?? 'Management / Shared'],
            ['Project Color', Project::colorFor($p)],
            ['Tracker / Folder Link', $p->tracker_url ?? ''],
            ['Progress Recap', $p->progress_recap ?? ''],
            ['Total Item', $total],
            ['Item Done', $done],
            ['Progress', ($total ? (int) round($done / $total * 100) : 0) . '%'],
            ['Exported At', now()->format('d/m/Y H:i')],
            ['Exported By', $this->exportedBy ?? ''],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A' => ['font' => ['bold' => true]],
        ];
    }
}