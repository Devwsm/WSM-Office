<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Models\OfficeSetting;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;

/**
 * CalendarController (Dashboard\Work)
 * ---------------------------------------------------------------------
 * 2026-09-15 — "Timeline Calendar" versi dashboard (sisi Owner/staf
 * dashboard_access, layouts.app). Sebelumnya link sidebar "Timeline
 * Calendar" di layouts.app langsung ngarah ke
 * Employee\WorkTrackerController::calendar() (App Mode, layouts.employee,
 * container mobile-first max-w-140) — dipilih supaya "Work Control" di
 * sisi dashboard punya 4 tab yang konsisten (Work Tracker, Timeline
 * Calendar, MoM & Memo, Rapat & Action Item), bukan 3 tab + 1 link yang
 * loncat keluar ke layout App Mode.
 *
 * Query/data month-grid-nya SAMA PERSIS dengan
 * Employee\WorkTrackerController::calendar() (satu sumber data WorkItem,
 * OfficeSetting, weekly rhythm) — cuma view target & layout-nya beda.
 * Employee\WorkTrackerController::calendar() TETAP ADA & TETAP DIPAKAI
 * di App Mode (link dari employee/_work-tracker.blade.php), route ini
 * bukan pengganti, cuma versi dashboard di samping versi App Mode yang
 * sudah ada.
 * ---------------------------------------------------------------------
 */
class CalendarController extends Controller
{
    private const WEEKLY_RHYTHM = [
        1 => ['day' => 'Senin', 'focus' => 'Alignment & Planning', 'mode' => 'WFO'],
        2 => ['day' => 'Selasa', 'focus' => 'Production & Decision', 'mode' => 'WFO'],
        3 => ['day' => 'Rabu', 'focus' => 'Delivery & Execution', 'mode' => 'WFO'],
        4 => ['day' => 'Kamis', 'focus' => 'Outreach & Development', 'mode' => 'WFH'],
        5 => ['day' => 'Jumat', 'focus' => 'Review & Improvement + Planning', 'mode' => 'WFH'],
    ];

    public function index()
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

        $office = OfficeSetting::current();
        $officeHours = Carbon::parse($office->work_start_time)->format('H:i') . '–' .
            Carbon::parse($office->normal_end_time)->format('H:i');
        $weeklyRhythm = collect(self::WEEKLY_RHYTHM)->map(fn(array $r) => [
            ...$r,
            'hours' => $r['mode'] === 'WFO' ? $officeHours : 'Flexible / remote',
        ])->all();

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

        // 2026-09-16 — 'color' ikut di-select biar $project->color kepake
        // langsung di legend (calendar.blade.php) lewat closure di bawah.
        $projects = Project::query()->orderBy('name')->get(['id', 'name', 'color']);
        $picOptions = User::query()
            ->whereIn('id', WorkItem::query()->whereNotNull('pic_employee_id')->distinct()->pluck('pic_employee_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dashboard.work.calendar', [
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