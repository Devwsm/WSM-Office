<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CalendarController (Dashboard\Work)
 * ---------------------------------------------------------------------
 * "Timeline Calendar" versi dashboard (layouts.app), tab ke-3 "Work
 * Control". Padanan `calendarPage()`/`calendarMarkup()` prototype v19+.
 *
 * 2026-09-28 — disamakan dengan prototype & tanpa data hardcode:
 *  - Weekly Rhythm (fokus/mode/jam per hari) dibaca dari
 *    OfficeSetting::weeklyRhythm() (kolom `weekly_rhythm`), bisa diubah
 *    lewat "Weekly Rhythm Settings" (butuh akses work=manage). Sebelumnya
 *    konstanta WEEKLY_RHYTHM yang disalin di 3 controller.
 *  - Tanggal mulai/selesai Project (`projects.start_date`/`end_date`)
 *    ikut tampil sebagai penanda ▶ / ■ seperti prototype.
 *  - Filter PIC ikut mencocokkan item "ALL TEAM" dan PIC tambahan
 *    (`additional_pic`, teks bebas) — sama seperti `taskHasPicV19()`.
 *
 * Query month-grid item SAMA dengan Employee\WorkTrackerController::
 * calendar() (App Mode), controller itu TETAP dipakai di App Mode.
 * ---------------------------------------------------------------------
 */
class CalendarController extends Controller
{
    /**
     * Maksimal chip yang langsung tampil per sel; sisanya jadi "+N item" yang
     * membuka popup harian (2026-10-03: turun dari 6 ke 3 sesuai permintaan tim).
     */
    private const VISIBLE_PER_CELL = 3;

    public function index(Request $request)
    {
        $monthParam = $request->query('month');
        // ?month= ngawur (diketik manual di URL) jatuh balik ke bulan ini, bukan error 500.
        try {
            $anchor = is_string($monthParam) && $monthParam !== ''
                ? Carbon::createFromFormat('!Y-m', $monthParam)->startOfMonth()
                : Carbon::today()->startOfMonth();
        } catch (\Exception) {
            $anchor = Carbon::today()->startOfMonth();
        }

        $projectFilter = $request->query('project') ? (int) $request->query('project') : null;
        $picFilter = $request->query('pic') ? (int) $request->query('pic') : null;
        $picUser = $picFilter ? User::query()->find($picFilter, ['id', 'name']) : null;

        $office = OfficeSetting::current();
        // 7 hari (Minggu..Sabtu) — Sabtu/Minggu berisi ritme event (header kalender).
        $weeklyRhythm = $office->calendarRhythm();

        $gridStart = $anchor->copy()->subDays($anchor->dayOfWeek);
        $gridEnd = $gridStart->copy()->addDays(41);
        $range = [$gridStart->toDateString(), $gridEnd->toDateString()];

        $itemsByDate = WorkItem::query()
            ->whereBetween('due_date', $range)
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->when($picUser, fn($q) => $this->wherePic($q, $picUser))
            ->with(['pic', 'additionalPics', 'project'])
            ->orderBy('item_no')
            ->orderBy('id')
            ->get()
            ->groupBy(fn(WorkItem $item) => $item->due_date->toDateString());

        // Penanda mulai/selesai project — hanya project yang terlihat oleh filter
        // (project terpilih; untuk filter PIC: dia lead-nya atau punya item untuk PIC itu).
        $markerProjects = Project::query()
            ->where(fn($q) => $q->whereBetween('start_date', $range)->orWhereBetween('end_date', $range))
            ->when($projectFilter, fn($q) => $q->where('id', $projectFilter))
            ->when($picUser, fn($q) => $q->where(
                fn($w) => $w->where('lead_employee_id', $picUser->id)
                    ->orWhereHas('workItems', fn($i) => $this->wherePic($i, $picUser))
            ))
            ->get();
        $startsByDate = $markerProjects->filter(fn(Project $p) => $p->start_date)->groupBy(fn(Project $p) => $p->start_date->toDateString());
        $endsByDate = $markerProjects->filter(fn(Project $p) => $p->end_date)->groupBy(fn(Project $p) => $p->end_date->toDateString());
        $projectsUrl = route('dashboard.work.projects.index');

        $chipForProject = function (Project $p, string $label) use ($projectsUrl): array {
            $color = Project::colorFor($p);

            return [
                'id' => null,
                'title' => $label,
                'pic' => null,
                'project' => $p->name,
                'progress' => null,
                'focus' => null,
                'color' => $color,
                'text' => Project::contrastTextFor($color),
                'url' => $projectsUrl,
                'done' => false,
            ];
        };

        $weeks = collect(range(0, 5))->map(function (int $week) use ($gridStart, $itemsByDate, $startsByDate, $endsByDate, $anchor, $weeklyRhythm, $chipForProject) {
            return collect(range(0, 6))->map(function (int $dow) use ($week, $gridStart, $itemsByDate, $startsByDate, $endsByDate, $anchor, $weeklyRhythm, $chipForProject) {
                $date = $gridStart->copy()->addDays($week * 7 + $dow);
                $key = $date->toDateString();

                $items = collect()
                    ->concat($startsByDate->get($key, collect())->map(fn(Project $p) => $chipForProject($p, '▶ ' . $p->name)))
                    ->concat($itemsByDate->get($key, collect())->map(function (WorkItem $item) {
                        $color = Project::colorFor($item->project);

                        return [
                            'id' => $item->id,
                            'title' => $item->title,
                            'pic' => $item->allPics()->pluck('name')->implode(' · ') ?: null,
                            'project' => $item->project?->name,
                            'progress' => $item->progress,
                            'focus' => $item->computedFocus(),
                            'color' => $color,
                            'text' => Project::contrastTextFor($color),
                            'url' => route('dashboard.work.tracker.index', array_filter(['project_id' => $item->project_id])),
                            'done' => $item->progress === 'Done',
                        ];
                    }))
                    ->concat($endsByDate->get($key, collect())->map(fn(Project $p) => $chipForProject($p, '■ ' . $p->name . ' end')))
                    ->values();

                return [
                    'date' => $date,
                    'outside' => $date->month !== $anchor->month,
                    'isToday' => $date->isToday(),
                    'key' => $key,
                    'rhythm' => $weeklyRhythm[$date->dayOfWeek] ?? null,
                    'all' => $items,
                    'visible' => $items->take(self::VISIBLE_PER_CELL),
                    'hidden' => $items->slice(self::VISIBLE_PER_CELL)->values(),
                ];
            });
        });

        // Data popup harian (satu JSON untuk seluruh grid): tanggal, ritme hari itu, dan semua item.
        $dayPopups = $weeks->flatten(1)->mapWithKeys(fn(array $cell) => [
            $cell['key'] => [
                'label' => $cell['date']->translatedFormat('l, j F Y'),
                'rhythm' => $cell['rhythm'] ? Arr::only($cell['rhythm'], ['focus', 'mode', 'hours']) : null,
                'items' => $cell['all']->values()->all(),
            ],
        ]);

        $projects = Project::query()->orderBy('name')->get(['id', 'name', 'color']);
        $picOptions = User::query()
            ->where(fn($q) => $q
                ->whereIn('id', WorkItem::query()->whereNotNull('pic_employee_id')->select('pic_employee_id'))
                ->orWhereIn('id', DB::table('work_item_additional_pics')->select('user_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Legend "Tanpa Project" hanya kalau memang ada item tanpa project di rentang yang tampil.
        $hasNoProjectItems = $itemsByDate->flatten(1)->contains(fn(WorkItem $i) => $i->project_id === null);

        return view('dashboard.work.calendar', [
            'weeks' => $weeks,
            'dayPopups' => $dayPopups,
            'rhythmOrder' => [1, 2, 3, 4, 5, 6, 0],
            'anchor' => $anchor,
            'projects' => $projects,
            'picOptions' => $picOptions,
            'projectFilter' => $projectFilter,
            'picFilter' => $picFilter,
            'weeklyRhythm' => $weeklyRhythm,
            'rhythmModes' => OfficeSetting::RHYTHM_MODES,
            'canManage' => (bool) $request->user()?->canManageModule('work'),
            'hasNoProjectItems' => $hasNoProjectItems,
            'noProjectColor' => Project::NO_PROJECT_COLOR,
        ]);
    }

    /**
     * Pindah tanggal deadline item lewat drag & drop kalender (desktop saja —
     * halaman hanya mengaktifkan drag di layar lebar dengan mouse; endpoint ini
     * tetap digerbang work=manage). Padanan prototype: ubah `due`, bukan section.
     */
    public function moveItem(Request $request, WorkItem $item)
    {
        $data = $request->validate(['due_date' => ['required', 'date_format:Y-m-d']]);

        $oldDate = $item->due_date?->toDateString();
        if ($oldDate !== $data['due_date']) {
            $item->update(['due_date' => $data['due_date']]);
            AuditLog::record(
                'Deadline task dipindah',
                'Task "' . $item->title . '" dipindah dari ' . ($oldDate ?? 'tanpa tanggal') . ' ke ' . $data['due_date'] . ' (kalender) oleh ' . $request->user()->name . '.',
                $request->user()
            );
        }

        return response()->json(['ok' => true, 'due_date' => $data['due_date']]);
    }

    /** Simpan Weekly Rhythm (Senin–Minggu) — padanan `saveWeeklyRhythmV19()` prototype. */
    public function updateRhythm(Request $request)
    {
        $data = $request->validate([
            'rhythm' => ['required', 'array'],
            'rhythm.*.focus' => ['nullable', 'string', 'max:80'],
            'rhythm.*.mode' => ['required', Rule::in(OfficeSetting::RHYTHM_MODES)],
            'rhythm.*.hours' => ['nullable', 'string', 'max:40'],
        ]);

        $office = OfficeSetting::query()->first();
        if (! $office) {
            return back()->with('error', 'Pengaturan kantor belum dikonfigurasi — isi dulu di Pengaturan Kantor.');
        }

        // 5 hari kerja + Sabtu/Minggu (ritme event). Hari yang tidak ikut dikirim
        // mempertahankan nilai tersimpan, bukan jatuh ke default.
        $stored = is_array($office->weekly_rhythm) ? $office->weekly_rhythm : [];
        $rhythm = [];
        foreach (OfficeSetting::DEFAULT_WEEKEND_RHYTHM + OfficeSetting::DEFAULT_WEEKLY_RHYTHM as $dow => $default) {
            if (! isset($data['rhythm'][$dow]) && isset($stored[$dow])) {
                $rhythm[$dow] = $stored[$dow];

                continue;
            }

            $row = $data['rhythm'][$dow] ?? [];
            $rhythm[$dow] = [
                'focus' => trim((string) ($row['focus'] ?? '')) ?: $default['focus'],
                'mode' => $row['mode'] ?? $default['mode'],
                'hours' => trim((string) ($row['hours'] ?? '')),
            ];
        }

        $office->update(['weekly_rhythm' => $rhythm]);
        AuditLog::record('Weekly rhythm diubah', 'Weekly Rhythm Timeline Calendar diubah oleh ' . $request->user()->name . '.', $request->user());

        return back()->with('status', 'Weekly rhythm disimpan.');
    }

    /** Kembalikan Weekly Rhythm ke default bawaan — padanan `resetWeeklyRhythmV19()`. */
    public function resetRhythm(Request $request)
    {
        $office = OfficeSetting::query()->first();
        if ($office) {
            $office->update(['weekly_rhythm' => null]);
            AuditLog::record('Weekly rhythm direset', 'Weekly Rhythm dikembalikan ke default oleh ' . $request->user()->name . '.', $request->user());
        }

        return back()->with('status', 'Weekly rhythm kembali ke default.');
    }

    /**
     * Filter PIC seperti `taskHasPicV19()` prototype: PIC utama, PIC tambahan
     * (relasi user), item "ALL TEAM", atau nama PIC muncul di catatan teks
     * `additional_pic` (data lama / pihak luar, bukan FK).
     */
    private function wherePic($query, User $pic)
    {
        return $query->where(function ($q) use ($pic) {
            $q->forPic($pic->id)
                ->orWhere('additional_pic', 'ALL TEAM')
                ->orWhere('additional_pic', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $pic->name) . '%');
        });
    }
}