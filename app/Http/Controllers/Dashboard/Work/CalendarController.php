<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
 *
 * 2026-10-20 — revisi permintaan tim:
 *  - Klik item di kalender (chip maupun daftar di popup harian) membuka
 *    MODAL detail yang langsung bisa diedit oleh yang punya akses
 *    work=manage (updateItem(), JSON), bukan pindah ke halaman Work Tracker.
 *  - Mode TAHUNAN (`?view=year&year=2026`): garis waktu project (Gantt) selama
 *    setahun untuk melihat berapa lama tiap project berjalan — lihat year().
 *
 * 2026-10-21 — tampilan disamakan dengan kalender Samsung (permintaan tim):
 *  - Mode TAHUNAN: di atas garis waktu project ada 12 bulan mini (Minggu-Sabtu);
 *    hari yang punya deadline diberi warna project, klik bulan -> langsung ke
 *    kalender bulan itu (lihat miniMonths()).
 *  - Mode BULAN: grid pas layar HP (chip satu baris, tanpa geser samping) —
 *    detail lengkap tetap lewat popup harian.
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
        if ($request->query('view') === 'year') {
            return $this->year($request);
        }

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
            ->sectionVisibleTo($request->user()) // section terbatas tidak muncul untuk yang tidak berhak
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

        // Data modal detail/edit item (dikirim sekali sebagai JSON, dicari lewat ID item).
        $itemDetails = $itemsByDate->flatten(1)->mapWithKeys(fn(WorkItem $item) => [
            $item->id => [
                'id' => $item->id,
                'title' => $item->title,
                'due_date' => $item->due_date?->toDateString(),
                'project' => $item->project?->name,
                'section' => $item->section,
                'progress' => $item->progress,
                'priority' => $item->priority,
                'link' => $item->link,
                'notes' => $item->notes,
                'pic' => $item->pic_employee_id,
                'pic_name' => $item->pic?->name,
                'extra' => $item->additionalPics->pluck('id')->values()->all(),
                'extra_names' => $item->additionalPics->pluck('name')->values()->all(),
                'pic_text' => $item->additional_pic, // catatan teks lama / "ALL TEAM" (bukan relasi)
                'focus' => $item->computedFocus(),
                'update_url' => route('dashboard.work.calendar.items.update', $item),
                'tracker_url' => route('dashboard.work.tracker.index', array_filter(['project_id' => $item->project_id ? [$item->project_id] : null])),
            ],
        ]);

        return view('dashboard.work.calendar', [
            'mode' => 'month',
            'itemDetails' => $itemDetails,
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
            'progressOptions' => WorkItem::PROGRESS_OPTIONS,
            'priorityOptions' => WorkItem::PRIORITIES,
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
        abort_unless($item->isAccessibleTo($request->user()), 403, 'Tidak ada akses ke item di dalam section ini.');

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

    /**
     * Mode TAHUNAN (2026-10-20): garis waktu project selama setahun (1 baris = 1
     * project) untuk melihat berapa lama project berjalan.
     *
     * Rentang project = tanggal Mulai/Selesai project; yang kosong diisi dari
     * deadline item paling awal/akhir (item yang boleh dilihat pengguna ini).
     * Project tanpa tanggal sama sekali dan tanpa item berdeadline tidak punya
     * rentang -> dicantumkan terpisah. Titik di garis = deadline item (dengan
     * filter PIC: hanya item PIC itu). Persentase progress = item Done / semua
     * item project yang terlihat.
     */
    private function year(Request $request)
    {
        $yearParam = (int) $request->query('year');
        $year = ($yearParam >= 2000 && $yearParam <= 2100) ? $yearParam : (int) Carbon::today()->year;

        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $totalDays = $yearStart->daysInYear;

        $projectFilter = $request->query('project') ? (int) $request->query('project') : null;
        $picFilter = $request->query('pic') ? (int) $request->query('pic') : null;
        $picUser = $picFilter ? User::query()->find($picFilter, ['id', 'name']) : null;
        $viewer = $request->user();

        // Semua item berdeadline yang boleh dilihat (lintas tahun: dipakai untuk rentang cadangan & progress).
        $items = WorkItem::query()
            ->sectionVisibleTo($viewer)
            ->whereNotNull('project_id')
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->get(['id', 'project_id', 'title', 'due_date', 'progress'])
            ->groupBy('project_id');

        // Filter PIC: hanya mempengaruhi project mana yang tampil dan titik deadline-nya.
        $picItemIds = $picUser
            ? $this->wherePic(WorkItem::query()->sectionVisibleTo($viewer), $picUser)->pluck('work_items.id')->flip()
            : null;

        $projects = Project::query()
            ->when($projectFilter, fn($q) => $q->where('id', $projectFilter))
            ->when($picUser, fn($q) => $q->where(
                fn($w) => $w->where('lead_employee_id', $picUser->id)
                    ->orWhereHas('workItems', fn($i) => $this->wherePic($i, $picUser))
            ))
            ->orderBy('name')
            ->get();

        // Carbon 3: diffInDays() mengembalikan float -> dibulatkan supaya selisih hari selalu bulat.
        $dayOffset = fn(Carbon $d): int => (int) round($yearStart->diffInDays($d, false));

        $rows = collect();
        $undated = collect();
        $outside = 0;

        foreach ($projects as $project) {
            $projectItems = $items->get($project->id, collect());
            $dated = $projectItems->filter(fn(WorkItem $i) => $i->due_date !== null);

            $from = $project->start_date?->copy()->startOfDay() ?? $dated->min('due_date')?->copy()->startOfDay();
            $to = $project->end_date?->copy()->startOfDay() ?? $dated->max('due_date')?->copy()->startOfDay();

            if (! $from && ! $to) {
                $undated->push($project->name);

                continue;
            }

            $from ??= $to->copy();
            $to ??= $from->copy();
            if ($to->lt($from)) {
                $to = $from->copy(); // tanggal selesai lebih awal dari mulai (salah isi): tampil 1 hari
            }

            if ($to->lt($yearStart) || $from->gt($yearEnd)) {
                $outside++;

                continue;
            }

            $visFrom = $from->lt($yearStart) ? $yearStart : $from;
            $visTo = $to->gt($yearEnd) ? $yearEnd : $to;
            $left = $dayOffset($visFrom) / $totalDays * 100;
            $width = max(0.7, ($dayOffset($visTo) - $dayOffset($visFrom) + 1) / $totalDays * 100);

            $ticks = $dated
                ->filter(fn(WorkItem $i) => $i->due_date->year === $year && ($picItemIds === null || $picItemIds->has($i->id)))
                ->sortBy('due_date')->take(200)
                ->map(fn(WorkItem $i) => [
                    'pos' => round($dayOffset($i->due_date->copy()->startOfDay()) / $totalDays * 100 + 0.5 / $totalDays * 100, 3),
                    'tip' => $i->title . ' · ' . $i->due_date->translatedFormat('j M Y') . ' · ' . $i->progress,
                    'done' => $i->progress === 'Done',
                ])->values();

            $days = (int) round($from->diffInDays($to)) + 1;
            $total = $projectItems->count();
            $done = $projectItems->where('progress', 'Done')->count();
            $color = Project::colorFor($project);

            $rows->push([
                'id' => $project->id,
                'name' => $project->name,
                'status' => $project->status,
                'priority' => $project->priority,
                'color' => $color,
                'text' => Project::contrastTextFor($color),
                'from' => $from->translatedFormat('j M Y'),
                'to' => $to->translatedFormat('j M Y'),
                // Rentang berasal dari tanggal project, atau (cadangan) dari deadline item.
                'derived' => ! $project->start_date || ! $project->end_date,
                'days' => $days,
                'duration' => $this->durationLabel($days),
                'left' => round($left, 3),
                'width' => round(min($width, 100 - $left), 3),
                'before' => $from->lt($yearStart),
                'after' => $to->gt($yearEnd),
                'items' => $total,
                'pct' => $total ? (int) round($done / $total * 100) : 0,
                'ticks' => $ticks,
                'url' => route('dashboard.work.tracker.index', ['project_id' => [$project->id]]),
            ]);
        }

        $rows = $rows->sortBy([['left', 'asc'], ['name', 'asc']])->values();

        // Kolom bulan (lebar sebanding jumlah hari) + garis batas bulan & penanda hari ini.
        $months = collect(range(1, 12))->map(function (int $m) use ($year, $yearStart, $totalDays, $projectFilter, $picFilter) {
            $start = Carbon::create($year, $m, 1)->startOfDay();

            return [
                'label' => $start->translatedFormat('M'),
                'left' => round((int) round($yearStart->diffInDays($start)) / $totalDays * 100, 3),
                'width' => round($start->daysInMonth / $totalDays * 100, 3),
                'url' => route('dashboard.work.calendar', array_filter(['month' => $start->format('Y-m'), 'project' => $projectFilter, 'pic' => $picFilter])),
            ];
        });

        $miniMonths = $this->miniMonths($year, $projectFilter, $picUser, $viewer);

        $today = Carbon::today();
        $todayPos = $today->year === $year ? round($dayOffset($today) / $totalDays * 100 + 0.5 / $totalDays * 100, 3) : null;

        return view('dashboard.work.calendar', [
            'mode' => 'year',
            'year' => $year,
            'yearRows' => $rows,
            'yearMonths' => $months,
            'miniMonths' => $miniMonths,
            'todayPos' => $todayPos,
            'undatedProjects' => $undated,
            'outsideCount' => $outside,
            'anchor' => Carbon::create($year, 1, 1),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'color']),
            'picOptions' => User::query()
                ->where(fn($q) => $q
                    ->whereIn('id', WorkItem::query()->whereNotNull('pic_employee_id')->select('pic_employee_id'))
                    ->orWhereIn('id', DB::table('work_item_additional_pics')->select('user_id')))
                ->orderBy('name')
                ->get(['id', 'name']),
            'projectFilter' => $projectFilter,
            'picFilter' => $picFilter,
            'canManage' => (bool) $viewer?->canManageModule('work'),
            'hasNoProjectItems' => false,
            'noProjectColor' => Project::NO_PROJECT_COLOR,
        ]);
    }

    /**
     * 12 bulan mini untuk mode TAHUN (gaya kalender Samsung): Minggu..Sabtu,
     * tiap hari yang punya deadline item diberi warna project (item pertama di
     * hari itu) + jumlah item untuk tooltip. Memakai aturan yang sama dengan
     * grid bulan: section terbatas tidak ikut, filter project/PIC berlaku.
     */
    private function miniMonths(int $year, ?int $projectFilter, ?User $picUser, User $viewer)
    {
        $byDate = WorkItem::query()
            ->sectionVisibleTo($viewer)
            ->whereBetween('due_date', [$year . '-01-01', $year . '-12-31'])
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->when($picUser, fn($q) => $this->wherePic($q, $picUser))
            ->with('project')
            ->orderBy('item_no')
            ->orderBy('id')
            ->get()
            ->groupBy(fn(WorkItem $item) => $item->due_date->toDateString());

        $today = Carbon::today();
        $filter = array_filter(['project' => $projectFilter, 'pic' => $picUser?->id]);

        return collect(range(1, 12))->map(function (int $m) use ($year, $byDate, $today, $filter) {
            $first = Carbon::create($year, $m, 1)->startOfDay();

            $days = collect(range(1, $first->daysInMonth))->map(function (int $day) use ($first, $byDate, $today) {
                $date = $first->copy()->day($day);
                $items = $byDate->get($date->toDateString(), collect());
                $color = $items->isNotEmpty() ? Project::colorFor($items->first()->project) : null;

                return [
                    'day' => $day,
                    'dow' => $date->dayOfWeek,
                    'color' => $color,
                    'count' => $items->count(),
                    'isToday' => $date->isSameDay($today),
                ];
            });

            return [
                'label' => $first->translatedFormat('F'),
                'short' => $first->translatedFormat('M'),
                'lead' => $first->dayOfWeek, // kotak kosong sebelum tanggal 1 (Minggu = 0)
                'days' => $days,
                'isCurrent' => $first->isSameMonth($today),
                'itemCount' => $days->sum('count'),
                'url' => route('dashboard.work.calendar', ['month' => $first->format('Y-m')] + $filter),
            ];
        });
    }

    /** "126 hari · ±4 bln" — lama project dalam hari, plus perkiraan bulan kalau sudah lebih dari sebulan. */
    private function durationLabel(int $days): string
    {
        if ($days < 45) {
            return $days . ' hari';
        }

        $months = round($days / 30.44, 1);
        $months = fmod($months, 1.0) === 0.0 ? (string) (int) $months : str_replace('.', ',', (string) $months);

        return $days . ' hari · ±' . $months . ' bln';
    }

    /**
     * Simpan perubahan 1 item dari modal kalender (JSON). Judul, tanggal, PIC 1-3,
     * progress, prioritas, link, dan catatan — project/section tidak diubah di sini
     * (itu lewat form Edit di Work Tracker). Aturan PIC sama dengan form task:
     * PIC 2/3 butuh PIC 1 dan tidak boleh orang yang sama.
     */
    public function updateItem(Request $request, WorkItem $item)
    {
        abort_unless($item->isAccessibleTo($request->user()), 403, 'Tidak ada akses ke item di dalam section ini.');

        $syncExtra = $request->has('additional_pic_ids');
        $request->merge([
            // Judul = satu baris logis: baris baru (mis. hasil paste) jadi spasi.
            'title' => trim((string) preg_replace('/\s*[\r\n]+\s*/', ' ', (string) $request->input('title'))),
            'additional_pic_ids' => collect((array) $request->input('additional_pic_ids', []))
                ->filter(fn($id) => $id !== null && $id !== '')->values()->all(),
        ]);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
            'pic_employee_id' => ['nullable', 'exists:users,id'],
            'additional_pic_ids' => ['nullable', 'array', 'max:' . WorkItem::MAX_ADDITIONAL_PICS],
            'additional_pic_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'progress' => ['required', Rule::in(WorkItem::PROGRESS_OPTIONS)],
            'priority' => ['required', Rule::in(WorkItem::PRIORITIES)],
            'link' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'title.required' => 'Judul tidak boleh kosong.',
            'additional_pic_ids.max' => 'Satu task maksimal 3 PIC (PIC 1 + 2 PIC tambahan).',
            'additional_pic_ids.*.distinct' => 'PIC 2 dan PIC 3 tidak boleh orang yang sama.',
            'link.url' => 'Link harus diawali http:// atau https://.',
        ]);

        $extra = array_map('intval', $data['additional_pic_ids'] ?? []);
        unset($data['additional_pic_ids']);
        $main = ($data['pic_employee_id'] ?? null) !== null && $data['pic_employee_id'] !== '' ? (int) $data['pic_employee_id'] : null;
        $data['pic_employee_id'] = $main;

        if ($syncExtra && $extra !== []) {
            if ($main === null) {
                throw ValidationException::withMessages(['additional_pic_ids' => 'Isi PIC 1 dulu sebelum menambah PIC 2 atau PIC 3.']);
            }
            if (in_array($main, $extra, true)) {
                throw ValidationException::withMessages(['additional_pic_ids' => 'PIC 2 / PIC 3 tidak boleh orang yang sama dengan PIC 1.']);
            }
        }

        foreach (['due_date', 'link', 'notes'] as $nullable) {
            $data[$nullable] = ($data[$nullable] ?? '') === '' ? null : $data[$nullable];
        }

        $changes = Audit::changes($item, $data, Audit::labels('work_item'));

        DB::transaction(function () use ($item, $data, $extra, $syncExtra) {
            $item->update($data);

            if ($syncExtra) {
                $item->additionalPics()->sync($extra);

                // Ada PIC tambahan nyata -> catatan teks lama (termasuk "ALL TEAM") tidak boleh menyimpang darinya.
                if ($extra !== [] && $item->additional_pic !== null) {
                    $item->update(['additional_pic' => null]);
                }
            }
        });

        AuditLog::record('Task diperbarui (kalender)', "Task \"{$item->title}\": {$changes}.", $request->user());

        return response()->json(['ok' => true]);
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