<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * DashboardController (Owner)
 * ---------------------------------------------------------------------
 * Halaman ringkasan utama Owner. Kartu "Pengajuan Pending" (sejak
 * Fase 5) gabungan izin/cuti pending + absen yang butuh perhatian
 * (lupa checkout) — kesepakatan Fase 5, biar satu angka aja yang perlu
 * dicek Owner tiap buka dashboard.
 * ---------------------------------------------------------------------
 */
class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $totalKaryawan = User::query()->count();
        $hadirHariIni = Attendance::query()->where('date', $today)->whereNotNull('clock_in_at')->count();

        $leavePending = LeaveRequest::query()->where('status', 'pending')->count();
        $attendanceNeedsAttention = Attendance::query()
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->where('date', '<', $today)
            ->count();

        return view('owner.dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'totalKaryawan' => $totalKaryawan,
            'leavePending' => $leavePending,
            'attendanceNeedsAttention' => $attendanceNeedsAttention,
            'pendingTotal' => $leavePending + $attendanceNeedsAttention,
        ]);
    }
}