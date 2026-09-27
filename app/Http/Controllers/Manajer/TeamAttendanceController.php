<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * TeamAttendanceController (Manajer only)
 * ---------------------------------------------------------------------
 * README Bab 2.1 #40 — "Team Overview" manajer, dipecah jadi 2 halaman
 * (2026-09-26, keputusan Arga) biar gak menuh-menuhin 1 halaman pas
 * karyawan & task makin banyak: ini bagian absensi HARI INI, pasangannya
 * TeamWorkController buat progress Work Tracker. Route: manajer.team.attendance.
 *
 * Beda dari Attendance\RecapController (yang juga ada tombol "Kelola
 * Tim" di header App Mode, buat siapa pun ber-modul `people`): recap
 * itu rekap sebulan + bisa pilih tanggal + koreksi jam. Halaman ini
 * SENGAJA cuma snapshot HARI INI, tanpa koreksi, tanpa filter tanggal —
 * "apa yang perlu saya follow-up sekarang", bukan laporan lengkap.
 *
 * Scope tim pakai User::visibleAttendanceUserIds() yang sudah ada
 * (dipakai juga di RecapController) — walau namanya kesannya khusus
 * attendance, isinya generik: Owner lihat semua, selain itu diri
 * sendiri + seluruh bawahan turunan (bukan cuma bawahan langsung) dari
 * `manager_id`. Route group ini cuma `role:manajer,owner`, jadi
 * HRD/Developer gak lewat sini sama sekali.
 * ---------------------------------------------------------------------
 */
class TeamAttendanceController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        /** @var User $me */
        $me = Auth::user();

        $users = User::query()
            ->whereIn('id', $me->visibleAttendanceUserIds())
            ->orderBy('name')
            ->get();

        $attendances = Attendance::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('date', $today)
            ->get()
            ->keyBy('user_id');

        // Sama seperti RecapController: cek izin/cuti disetujui yang nyakup
        // hari ini, biar "Belum Absen" gak salah tampil buat yang lagi cuti.
        $leaves = LeaveRequest::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->keyBy('user_id');

        $overtimes = OvertimeRequest::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('status', 'disetujui')
            ->where('date', $today)
            ->get()
            ->keyBy('user_id');

        $setting = OfficeSetting::current();

        $rows = $users->map(function (User $user) use ($attendances, $leaves, $overtimes, $setting) {
            $attendance = $attendances->get($user->id);
            $leave = $leaves->get($user->id);

            return [
                'user' => $user,
                'attendance' => $attendance,
                'leave' => $leave,
                'overtime' => $overtimes->get($user->id),
                'statusLabel' => $leave
                    ? $leave->typeLabel()
                    : ($attendance ? $attendance->statusLabel($setting) : 'Belum Absen'),
                'badgeClass' => $leave
                    ? 'badge-wsm-blue'
                    : ($attendance ? $attendance->statusBadgeClass($setting) : 'badge-wsm-gray'),
            ];
        });

        $summary = [
            'total' => $rows->count(),
            'sudahAbsen' => $rows->filter(fn(array $r) => $r['attendance'] !== null)->count(),
            'belumAbsen' => $rows->filter(fn(array $r) => $r['attendance'] === null && $r['leave'] === null)->count(),
            'cuti' => $rows->filter(fn(array $r) => $r['leave'] !== null)->count(),
            'lembur' => $rows->filter(fn(array $r) => $r['overtime'] !== null)->count(),
        ];

        return view('manajer.team.attendance', [
            'rows' => $rows,
            'summary' => $summary,
            'date' => $today,
        ]);
    }
}