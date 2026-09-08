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
 * Fase 7 — tutup paksa sesi absen yang lupa checkout, baik yang
 * tanggalnya udah lewat (hari kemarin dst.) MAUPUN sesi hari ini yang
 * udah lewat jam selesai window normal (`normal_end_time`, default
 * 20:00) — padanan `ensureAutoCloseAttendance()` di prototype v18, yang
 * juga nutup paksa sesi HARI YANG SAMA begitu app dibuka lewat jam
 * segitu, bukan nunggu hari berikutnya. SENGAJA dipisah jadi class
 * sendiri (bukan method private di AttendanceController) karena
 * dipanggil dari 2 tempat: `Employee\AttendanceController` (sebelum
 * clockIn/clockOut/history) dan `Employee\HomeController` (Home
 * biasanya halaman PERTAMA yang dibuka user, jadi reconcile paling
 * kepenuhi triggernya di situ).
 *
 * Gak ada cron di hosting cPanel shared, jadi reconcile-nya "on-demand"
 * — kepicu tiap ada yang buka halaman terkait, bukan real-time jam
 * 20:00 persis (sama batasan prototype yang browser/local-storage
 * based, lihat README prototype bagian "Prototype limitation").
 * ---------------------------------------------------------------------
 */
class AttendanceReconciler
{
    /**
     * Cutoff-nya beda tergantung situasi:
     * - Tanggal SUDAH LEWAT (bukan hari ini lagi):
     *   - Gak ada Lembur disetujui & mode Kantor/WFH -> ditutup di jam
     *     SELESAI window normal (`normal_end_time`) hari itu.
     *   - Ada Lembur disetujui, ATAU mode Lapangan/Gigs (multi-sesi,
     *     emang gak diklem ke window normal) -> ditutup di 23:59:59
     *     hari itu.
     * - Tanggal HARI INI, mode Kantor/WFH, gak ada Lembur disetujui,
     *   DAN sekarang udah lewat `normal_end_time` -> ditutup di jam
     *   `normal_end_time` hari ini juga (gak nunggu besok). Mode
     *   Lapangan/Gigs atau yang punya Lembur disetujui hari ini
     *   dibiarkan terbuka — harinya emang belum "berakhir".
     */
    public function reconcile(int $userId): void
    {
        $today = Carbon::today();

        $openSessions = Attendance::query()
            ->where('user_id', $userId)
            ->whereNull('clock_out_at')
            ->whereDate('date', '<=', $today)
            ->get();

        if ($openSessions->isEmpty()) {
            return;
        }

        $setting = OfficeSetting::current();
        $now = Carbon::now();

        DB::transaction(function () use ($openSessions, $setting, $userId, $today, $now) {
            foreach ($openSessions as $attendance) {
                $dateString = $attendance->date->toDateString();
                $isToday = $attendance->date->isSameDay($today);
                $overtimeApproved = OvertimeRequest::approvedFor($userId, $dateString) !== null;
                $normalEnd = $setting->normalEndOn($attendance->date);

                if ($isToday) {
                    // Hari masih berjalan buat mode multi-sesi atau yang
                    // punya Lembur disetujui, dan buat Kantor/WFH yang
                    // belum lewat jam selesai window normal -> biarin
                    // terbuka, belum waktunya ditutup paksa.
                    if ($attendance->isMultiSessionMode() || $overtimeApproved || $now->lt($normalEnd)) {
                        continue;
                    }

                    $cutoff = $normalEnd;
                } else {
                    $cutoff = ($attendance->isMultiSessionMode() || $overtimeApproved)
                        ? $attendance->date->copy()->endOfDay()
                        : $normalEnd;
                }

                $attendance->fill([
                    'clock_out_at' => $cutoff,
                    'auto_closed' => true,
                ]);
                $attendance->save();
            }
        });
    }
}