<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Carbon;

/**
 * DashboardController (Owner)
 * ---------------------------------------------------------------------
 * Halaman ringkasan utama Owner. Kartu "Pengajuan Pending" (sejak
 * Fase 5) gabungan izin/cuti pending + absen yang butuh perhatian
 * (lupa checkout) — kesepakatan Fase 5, biar satu angka aja yang perlu
 * dicek Owner tiap buka dashboard.
 *
 * Fase 16 — 2 kartu yang dari awal placeholder ("—", "Data aktif mulai
 * Fase 7"/"Fase 9") AKHIRNYA diisi data beneran, sekarang Work Tracker
 * (Fase 9) & Employee Contracts (Fase 11) udah ada. Catatan lama itu
 * ketinggalan zaman — gak kehapus dari awal karena gak ada yang balik
 * ngecek view ini pas fase-fase terkait selesai.
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

        // "Berjalan" = belum kelar & belum ditunda (Done/Postpone
        // dianggap selesai urusannya, gak perlu nyantol di ringkasan).
        $tugasBerjalan = WorkItem::query()->whereNotIn('progress', ['Done', 'Postpone'])->count();

        $kontrakAkanHabis = EmployeeContract::query()
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, Carbon::today()->addDays(30)->toDateString()])
            ->count();

        return view('owner.dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'totalKaryawan' => $totalKaryawan,
            'leavePending' => $leavePending,
            'attendanceNeedsAttention' => $attendanceNeedsAttention,
            'pendingTotal' => $leavePending + $attendanceNeedsAttention,
            'tugasBerjalan' => $tugasBerjalan,
            'kontrakAkanHabis' => $kontrakAkanHabis,
        ]);
    }
}