<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\EmployeeContract;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
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
 *
 * Audit visual vs prototype (2026-09-15) — nambah 3 section yang
 * kelewat sejak Fase 0 (bukan pernah dihapus, dari awal emang belum
 * ada di CEO Dashboard walau datanya udah tersedia lewat model lain):
 * hero "Check Your Team Space", strip Weekly Rhythm 5 hari (Senin-
 * Jumat, datanya dari OfficeSetting::weeklyRhythm() — 2026-09-28
 * tidak lagi salinan hardcode per controller), card Upcoming Birthday & Work Anniversary
 * (window 60 hari, padanan card "Team Moments" tapi versi Owner —
 * beda dari `Employee\HomeController::$teamMoments` yang window-nya
 * 45 hari & di-cap 6 baris, di sini SENGAJA gak di-cap & 60 hari biar
 * sama persis label prototype "UPCOMING 60 DAYS"), dan card Service
 * Length (pakai `User::serviceDurationLabel()` yang sudah ada dari
 * App Mode Milestones, cuma belum pernah dipanggil dari sisi Owner).
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

        // Weekly Rhythm — jam kerja WFO ambil dari OfficeSetting asli,
        // sama pola kayak Employee\WorkTrackerController::calendar().
        $office = OfficeSetting::current();
        // 2026-09-28 — rhythm (fokus/mode/jam per hari) dibaca dari
        // OfficeSetting::weeklyRhythm(), diubah lewat Weekly Rhythm
        // Settings di Timeline Calendar — tidak ada lagi salinan hardcode.
        $weeklyRhythm = $office->weeklyRhythm();

        // Upcoming Birthday & Work Anniversary — semua karyawan
        // (termasuk Owner sendiri, prototype juga tidak exclude
        // viewer), window 60 hari sesuai label prototype, diurut dari
        // yang paling dekat. Query sama pola dengan $teamMoments di
        // Employee\HomeController, cuma window & cap-nya beda (lihat
        // catatan class).
        $teamMoments = User::query()
            ->orderBy('name')
            ->get()
            ->flatMap(function (User $u) {
                $rows = [];

                if (($b = $u->nextBirthdayOccurrence()) && $b['days'] <= 60) {
                    $rows[] = ['type' => 'birthday', 'user' => $u, 'date' => $b['date'], 'days' => $b['days'], 'years' => null];
                }

                if (($a = $u->nextWorkAnniversaryOccurrence()) && $a['days'] <= 60) {
                    $rows[] = ['type' => 'anniversary', 'user' => $u, 'date' => $a['date'], 'days' => $a['days'], 'years' => $a['years']];
                }

                return $rows;
            })
            ->sortBy(fn($row) => $row['date']->toDateString())
            ->values();

        // Service Length — semua karyawan aktif diurut nama, label
        // "lama bekerja" dari User::serviceDurationLabel() (null kalau
        // join_date belum diisi, blade nampilin "Set join date").
        $serviceLengths = User::query()->orderBy('name')->get();

        return view('owner.dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'totalKaryawan' => $totalKaryawan,
            'leavePending' => $leavePending,
            'attendanceNeedsAttention' => $attendanceNeedsAttention,
            'pendingTotal' => $leavePending + $attendanceNeedsAttention,
            'tugasBerjalan' => $tugasBerjalan,
            'kontrakAkanHabis' => $kontrakAkanHabis,
            'weeklyRhythm' => $weeklyRhythm,
            'teamMoments' => $teamMoments,
            'serviceLengths' => $serviceLengths,
        ]);
    }
}