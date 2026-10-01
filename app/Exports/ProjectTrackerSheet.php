<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\WorkItem;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Tracker" — satu baris per task, dikelompokkan per section menurut
 * urutan section yang diatur di WSM (project_sections), lalu nomor item.
 * Judul kolom sengaja huruf besar & berurutan seperti tracker Google Sheet tim,
 * supaya file ini bisa ditempel/diunggah ke sheet tanpa ubah susunan.
 */
class ProjectTrackerSheet implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public const HEADINGS = ['SECTION', 'NO', 'ITEM', 'DATE', 'FOCUS', 'PIC', 'PROGRESS', 'NOTE', 'LINK', 'ID WSM'];

    public function __construct(private readonly Project $project) {}

    public function title(): string
    {
        return 'Tracker';
    }

    public function array(): array
    {
        $items = WorkItem::query()
            ->with(['pic:id,name', 'additionalPics:id,name'])
            ->where('project_id', $this->project->id)
            ->get();

        // Section terdaftar dulu (urutan yang diatur), sisanya menyusul menurut
        // kemunculan pertama, item tanpa section paling akhir.
        $order = ProjectSection::query()
            ->where('project_id', $this->project->id)
            ->orderBy('sort_order')->orderBy('id')
            ->pluck('name')->map(fn($n) => (string) $n)->all();
        $rank = fn(string $section): int => ($i = array_search($section, $order, true)) !== false
            ? $i
            : ($section === '' ? PHP_INT_MAX : count($order) + 1);

        $sorted = $items->sortBy([
            fn(WorkItem $a, WorkItem $b) => $rank((string) $a->section) <=> $rank((string) $b->section),
            fn(WorkItem $a, WorkItem $b) => (is_numeric($a->item_no) ? (int) $a->item_no : PHP_INT_MAX)
                <=> (is_numeric($b->item_no) ? (int) $b->item_no : PHP_INT_MAX),
            fn(WorkItem $a, WorkItem $b) => $a->id <=> $b->id,
        ]);

        $rows = [self::HEADINGS];

        foreach ($sorted as $item) {
            $rows[] = [
                (string) $item->section,
                $item->item_no,
                $item->title,
                optional($item->due_date)->format('d/m/Y') ?? '',
                $item->computedFocus(),
                $item->picLabel(''),
                $item->progress,
                $item->notes ?? '',
                $item->link ?? '',
                $item->id,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}