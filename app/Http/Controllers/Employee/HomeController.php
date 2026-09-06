<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Memo;
use App\Models\OfficeSetting;
use App\Support\AttendanceReconciler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * HomeController (Employee)
 * ---------------------------------------------------------------------
 * Halaman utama sisi Karyawan/Manajer/HRD/Owner. Kartu kehadiran
 * fungsional sejak Fase 4; sejak Fase 5 kartu ini juga cek apakah hari
 * ini lagi ada izin/cuti yang disetujui.
 *
 * Fase 7 nambah:
 * - `AttendanceReconciler::reconcile()` dipanggil di sini juga (bukan
 *   cuma di AttendanceController) — Home biasanya halaman PERTAMA yang
 *   dibuka, jadi auto-close sesi kemarin yang lupa checkout paling
 *   kepenuhi triggernya di sini, bukan nunggu user buka Riwayat/absen
 *   dulu.
 * - `$sessions`/`$openSession`/`$canStartNewSession` — dukung
 *   multi-sesi Lapangan/Gigs. `$attendance` yang dioper ke view
 *   sekarang representasi "sesi yang relevan buat ditampilin" (sesi
 *   terbuka kalau ada, kalau enggak sesi terakhir hari ini), BUKAN
 *   lagi asumsi "1 baris = 1 hari" kayak Fase 4.
 * ---------------------------------------------------------------------
 */
class HomeController extends Controller
{
    public function index(AttendanceReconciler $reconciler)
    {
        $reconciler->reconcile((int) Auth::id());

        $today = Carbon::today()->toDateString();

        $sessions = Attendance::sessionsFor((int) Auth::id(), $today);
        $openSession = $sessions->first(fn(Attendance $a) => $a->clock_in_at && ! $a->clock_out_at);
        $latestSession = $sessions->last();
        $attendance = $openSession ?? $latestSession;

        // Lapangan/Gigs boleh check-in sesi baru lagi setelah sesi
        // sebelumnya checkout. Kantor/WFH tetap 1 sesi/hari.
        $canStartNewSession = ! $openSession
            && ($sessions->isEmpty() || $latestSession->isMultiSessionMode());

        // Reminder kalau ada absen hari sebelumnya yang lupa di-checkout.
        // SETELAH reconcile() di atas, ini seharusnya jarang kena lagi
        // (auto-close udah nutup duluan) — tetap dicek buat jaga-jaga
        // kalau ada race condition, atau baris yang auto-close-nya
        // barusan (biar user tetap tau itu kejadian, bukan cuma
        // diam-diam ditutup sistem tanpa pemberitahuan apa pun).
        $forgottenAttendance = Attendance::query()
            ->where('user_id', Auth::id())
            ->where('date', '<', $today)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->orderByDesc('date')
            ->first();

        $todayLeave = LeaveRequest::approvedFor((int) Auth::id(), $today);

        // Kartu "Info dari Owner" — sengaja kelihatan buat SEMUA role
        // internal terlepas dari dashboard_access (Fase 6a), karena ini
        // pengumuman ke tim, bukan modul kerja yang butuh akses.
        $memos = Memo::query()->with('creator')->latestFirst()->limit(3)->get();

        return view('employee.home', [
            'attendance' => $attendance,
            'sessions' => $sessions,
            'canStartNewSession' => $canStartNewSession,
            'forgottenAttendance' => $forgottenAttendance,
            'todayLeave' => $todayLeave,
            'officeSetting' => OfficeSetting::current(),
            'memos' => $memos,
        ]);
    }
}