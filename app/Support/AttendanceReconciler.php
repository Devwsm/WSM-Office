<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\OfficeSetting;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * AttendanceReconciler
 * ---------------------------------------------------------------------
 * Fase 7 — tutup paksa sesi absen yang lupa checkout DAN tanggalnya
 * udah lewat (bukan hari ini lagi). SENGAJA dipisah jadi class sendiri
 * (bukan method private di AttendanceController) karena dipanggil dari
 * 2 tempat: `Employee\AttendanceController` (sebelum clockIn/clockOut/
 * history) dan `Employee\HomeController` (Home biasanya halaman
 * PERTAMA yang dibuka user, jadi reconcile paling kepenuhi triggernya
 * di situ).
 *
 * Gak ada cron di hosting cPanel shared, jadi reconcile-nya "on-demand"
 * — kepicu tiap ada yang buka halaman terkait, bukan real-time jam
 * 20:00 persis.
 * ---------------------------------------------------------------------
 */
class AttendanceReconciler
{
    /**
     * Cutoff-nya beda tergantung ada Lembur disetujui hari itu atau
     * enggak:
     * - Gak ada Lembur disetujui & mode Kantor/WFH -> ditutup di jam
     *   SELESAI window normal (`normal_end_time`, default 20:00) hari
     *   itu.
     * - Ada Lembur disetujui, ATAU mode Lapangan/Gigs (multi-sesi,
     *   emang gak diklem ke window normal) -> ditutup di 23:59:59
     *   hari itu.
     */
    public function reconcile(int $userId): void
    {
        $openPastSessions = Attendance::query()
            ->where('user_id', $userId)
            ->whereNull('clock_out_at')
            ->whereDate('date', '<', Carbon::today())
            ->get();

        if ($openPastSessions->isEmpty()) {
            return;
        }

        $setting = OfficeSetting::current();

        DB::transaction(function () use ($openPastSessions, $setting, $userId) {
            foreach ($openPastSessions as $attendance) {
                $dateString = $attendance->date->toDateString();
                $overtimeApproved = OvertimeRequest::approvedFor($userId, $dateString) !== null;

                $cutoff = ($attendance->isMultiSessionMode() || $overtimeApproved)
                    ? $attendance->date->copy()->endOfDay()
                    : $setting->normalEndOn($attendance->date);

                $attendance->fill([
                    'clock_out_at' => $cutoff,
                    'auto_closed' => true,
                ]);
                $attendance->save();
            }
        });
    }
}