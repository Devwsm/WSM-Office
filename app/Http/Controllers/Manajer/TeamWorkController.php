<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Auth;

/**
 * TeamWorkController (Manajer only)
 * ---------------------------------------------------------------------
 * README Bab 2.1 #40 — pasangan TeamAttendanceController: progress
 * Work Tracker per anggota tim (task overdue, jumlah task aktif per
 * orang). Route: manajer.team.work. Scope tim sama persis
 * (User::visibleAttendanceUserIds()) — lihat catatan lengkap di
 * TeamAttendanceController, gak diulang di sini.
 *
 * "Overdue" dihitung lewat WorkItem::computedFocus() === 'KELEWAT' —
 * SAMA logic yang dipakai badge fokus di board Work Tracker
 * (WorkTrackerBoardController) & kartu "My Work Tracker" App Mode
 * (Employee\HomeController), bukan hitungan baru. "Task aktif" =
 * progress != 'Done', sama definisi yang dipakai kartu "My Work
 * Tracker" itu juga.
 * ---------------------------------------------------------------------
 */
class TeamWorkController extends Controller
{
    public function index()
    {
        /** @var User $me */
        $me = Auth::user();

        $users = User::query()
            ->whereIn('id', $me->visibleAttendanceUserIds())
            ->orderBy('name')
            ->get();

        $items = WorkItem::query()
            ->whereIn('pic_employee_id', $users->pluck('id'))
            ->where('progress', '!=', 'Done')
            ->with('project')
            ->orderByRaw('due_date IS NULL, due_date')
            ->get()
            ->groupBy('pic_employee_id');

        $rows = $users
            ->map(function (User $user) use ($items) {
                $tasks = $items->get($user->id, collect());
                $overdue = $tasks->filter(fn(WorkItem $task) => $task->computedFocus() === 'KELEWAT')->values();

                return [
                    'user' => $user,
                    'tasks' => $tasks,
                    'overdueCount' => $overdue->count(),
                    'openCount' => $tasks->count(),
                ];
            })
            // Siapa yang paling perlu di-follow-up duluan: overdue
            // terbanyak, lalu total task aktif terbanyak. sort() (bukan
            // sortBy()) dipakai sengaja -- sortBy() dengan array kriteria
            // mengharapkan pasangan [key, arah], bukan closure komparator
            // 2-argumen seperti ini.
            ->sort(fn(array $a, array $b) => [$b['overdueCount'], $b['openCount']] <=> [$a['overdueCount'], $a['openCount']])
            ->values();

        return view('manajer.team.work', [
            'rows' => $rows,
        ]);
    }
}