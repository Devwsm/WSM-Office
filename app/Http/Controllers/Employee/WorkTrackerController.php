<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * WorkTrackerController (Employee)
 * ---------------------------------------------------------------------
 * App Mode (2026-09-09) — padanan `employeeTasksMarkup()` (My Work
 * Tracker, embedded di Home, lihat HomeController::index() &
 * employee/_work-tracker.blade.php) dan "Shared Workload Calendar"
 * (`calendar()` di bawah, padanan `renderEmployeeSharedCalendarV21` /
 * `v21EmployeeCalendarGrid` di prototype v32).
 *
 * KOREKSI (2026-09-13) — audit sebelumnya (komentar lama di sini)
 * bilang versi final prototype "cuma 14 hari ke depan, TANPA filter
 * Project/PIC". Itu SALAH — dikonfirmasi langsung dari screenshot app
 * prototype yang BENERAN JALAN (bukan cuma baca kode statis, yang
 * gampang ketuker banyaknya versi function senama v9/v18/v21 di file
 * yang sama): Shared Workload Calendar versi final itu FULL MONTH GRID
 * (Minggu-Sabtu, 6 baris) dengan navigasi bulan (←/Today/→), filter
 * Project & PIC, "weekly rhythm" strip (fokus kerja per hari
 * Senin-Jumat), task berwarna sesuai project, dan legend warna project
 * di bawah — persis `calendar()` versi ini sekarang.
 *
 * "My Work Tracker" = WorkItem dengan `pic_employee_id` = user yang
 * login. `additional_pic` (kolom string bebas, BUKAN FK) sengaja TIDAK
 * ikut nentuin "punya siapa" — gak bisa dicocokkan ke user_id manapun,
 * cuma teks tambahan buat dibaca manusia.
 *
 * SCOPE YANG SENGAJA BELUM DIKERJAKAN (v1, lihat README): update
 * progress/notes langsung dari Home (prototype punya dropdown ubah
 * status + tombol "Update Note" di tiap task-card — lihat
 * `taskCardMarkupV9`, parameter `employeeModeView=true`). Ini VIEW-ONLY
 * dulu — nulis/ubah WorkItem butuh route+validasi+authorization
 * terpisah yang belum digarap, supaya gak nyampur sama quick win Team
 * Moments/Paid Leave yang murni read-only dari data yang udah ada.
 *
 * "Weekly Rhythm" (`WEEKLY_RHYTHM` konstanta di bawah) — hari & fokus
 * kerja per hari SENGAJA hardcoded (prototype punya UI Owner buat ubah
 * ini per hari, WSM-Office belum ada tempat nyimpennya di DB). TAPI
 * jam kerjanya (2026-09-14, fix "belum pakai data asli") SEKARANG
 * ambil dari `OfficeSetting::current()` yang beneran (bukan angka
 * hardcoded terpisah yang bisa beda sama Pengaturan Kantor asli) —
 * kalau Owner ubah jam kerja di sana, kalender ini otomatis ikut
 * berubah. Hari WFH tetap "Flexible / remote" (gak ada kolom jam WFH
 * di OfficeSetting, itu bukan kebijakan berbasis jam).
 * "Project color" (2026-09-16) SEKARANG baca `$project->color` asli
 * (form "Kelola Projects" punya field Warna) lewat
 * `Project::colorFor()`/`contrastTextFor()` — sebelumnya warna
 * hardcoded/deterministik dari `project_id % jumlah palet` karena kolom
 * `color`-nya belum ada sama sekali (lihat migration
 * `add_color_to_projects_table`).
 * ---------------------------------------------------------------------
 */
class WorkTrackerController extends Controller
{
    /**
     * Padanan V19_DEFAULT_RHYTHM di prototype — fokus & mode kerja per
     * hari (Minggu=0 s.d. Sabtu=6, cuma Senin-Jumat yang diisi). Jam
     * kerja WFO-nya DIISI DINAMIS di calendar() dari OfficeSetting asli
     * (lihat komentar class), bukan angka statis di sini.
     */
    private const WEEKLY_RHYTHM = [
        1 => ['day' => 'Senin', 'focus' => 'Alignment & Planning', 'mode' => 'WFO'],
        2 => ['day' => 'Selasa', 'focus' => 'Production & Decision', 'mode' => 'WFO'],
        3 => ['day' => 'Rabu', 'focus' => 'Delivery & Execution', 'mode' => 'WFO'],
        4 => ['day' => 'Kamis', 'focus' => 'Outreach & Development', 'mode' => 'WFH'],
        5 => ['day' => 'Jumat', 'focus' => 'Review & Improvement + Planning', 'mode' => 'WFH'],
    ];

    /** Palet warna & kontras teks project sekarang dipusatkan di Project::nextPaletteColor()/colorFor()/contrastTextFor() — lihat catatan class di atas. */

    /**
     * "Shared Workload Calendar" — full month grid, padanan
     * `renderEmployeeSharedCalendarV21()`/`v21EmployeeCalendarGrid()`.
     * `?month=YYYY-MM` buat navigasi, `?project=` & `?pic=` buat filter.
     */
    public function calendar()
    {
        $monthParam = Request::query('month');
        // ?month= ngawur (diketik manual di URL) jatuh balik ke bulan ini, bukan error 500.
        try {
            $anchor = $monthParam
                ? Carbon::createFromFormat('!Y-m', $monthParam)->startOfMonth()
                : Carbon::today()->startOfMonth();
        } catch (\Exception) {
            $anchor = Carbon::today()->startOfMonth();
        }

        $projectFilter = Request::query('project') ? (int) Request::query('project') : null;
        $picFilter = Request::query('pic') ? (int) Request::query('pic') : null;

        // Fix (2026-09-14) — jam kerja WFO diambil dari OfficeSetting
        // ASLI (bukan hardcoded), biar sinkron sama Pengaturan Kantor.
        $office = OfficeSetting::current();
        $officeHours = Carbon::parse($office->work_start_time)->format('H:i') . '–' .
            Carbon::parse($office->normal_end_time)->format('H:i');
        $weeklyRhythm = collect(self::WEEKLY_RHYTHM)->map(fn(array $r) => [
            ...$r,
            'hours' => $r['mode'] === 'WFO' ? $officeHours : 'Flexible / remote',
        ])->all();

        // Grid 6 baris x 7 kolom (42 sel), mulai dari hari Minggu SEBELUM
        // tanggal 1 — persis pola `start=new Date(y,m,1-first.getDay())`
        // di prototype.
        $firstOfMonth = $anchor->copy();
        $gridStart = $firstOfMonth->copy()->subDays($firstOfMonth->dayOfWeek);

        $rangeEnd = $gridStart->copy()->addDays(41);
        $itemsByDate = WorkItem::query()
            ->whereBetween('due_date', [$gridStart->toDateString(), $rangeEnd->toDateString()])
            ->when($projectFilter, fn($q) => $q->where('project_id', $projectFilter))
            ->when($picFilter, fn($q) => $q->where('pic_employee_id', $picFilter))
            ->with(['pic', 'project'])
            ->get()
            ->groupBy(fn(WorkItem $item) => $item->due_date->toDateString());

        $weeks = collect(range(0, 5))->map(function (int $week) use ($gridStart, $itemsByDate, $anchor, $weeklyRhythm) {
            return collect(range(0, 6))->map(function (int $dow) use ($week, $gridStart, $itemsByDate, $anchor, $weeklyRhythm) {
                $date = $gridStart->copy()->addDays($week * 7 + $dow);
                $dateKey = $date->toDateString();
                $dayItems = $itemsByDate->get($dateKey, collect());

                return [
                    'date' => $date,
                    'outside' => $date->month !== $anchor->month,
                    'isToday' => $date->isToday(),
                    'rhythm' => $weeklyRhythm[$date->dayOfWeek] ?? null,
                    'items' => $dayItems->map(fn(WorkItem $item) => [
                        'title' => $item->title,
                        'pic' => $item->pic?->name,
                        'color' => Project::colorFor($item->project),
                        'text' => Project::contrastTextFor(Project::colorFor($item->project)),
                    ]),
                ];
            });
        });

        $projects = Project::query()->orderBy('name')->get(['id', 'name', 'color']);
        $picOptions = User::query()
            ->whereIn('id', WorkItem::query()->whereNotNull('pic_employee_id')->distinct()->pluck('pic_employee_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('employee.work-tracker.calendar', [
            'weeks' => $weeks,
            'anchor' => $anchor,
            'projects' => $projects,
            'picOptions' => $picOptions,
            'projectFilter' => $projectFilter,
            'picFilter' => $picFilter,
            'weeklyRhythm' => $weeklyRhythm,
            'projectColor' => fn(?int $id) => Project::colorFor($projects->firstWhere('id', $id)),
        ]);
    }
}