<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceAbsence;
use App\Models\LeaveRequest;
use App\Models\OfficeSetting;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * PayrollCalculator
 * ---------------------------------------------------------------------
 * Satu-satunya tempat aturan hitung gaji (2026-09-29), dipakai Generate,
 * halaman detail, dan slip PDF supaya angkanya tidak bisa beda-beda.
 *
 * Aturan (mengikuti prototype v32 `calcPayroll` versi v18, bukan versi lama):
 * - tarif harian   = gaji pokok ÷ pembagi hari kerja (Pengaturan Kantor, bawaan 22)
 * - tarif per jam  = tarif harian ÷ jam kerja wajib per hari
 * - hari absen     = hanya tanggal yang DITANDAI Absen (AttendanceAbsence) × tarif harian.
 *                    Tanggal yang ada clock-in atau tercakup izin/cuti/sakit disetujui
 *                    tidak dihitung, walau ada penandanya.
 * - kurang jam     = akumulasi menit sebulan → per blok (toleransi: sisa di bawah satu
 *                    blok tidak dipotong bulan itu) × jam per blok × tarif per jam
 * - lembur         = jumlah TANGGAL lembur disetujui × flat rate karyawan
 * - terlambat      = tidak ada potongan sendiri (prototype final `late: 0`)
 * Total dipatok minimal 0 di PayrollRecord::recalculateTotal().
 * ---------------------------------------------------------------------
 */
class PayrollCalculator
{
    /**
     * @return array{
     *   base: float, divisor: int, daily_rate: float, hourly_rate: float,
     *   overtime_count: int, overtime_amount: float,
     *   shortage: array{total_shortage_minutes:int, blocks:int, remainder_minutes:int},
     *   shortage_deduction: float,
     *   absent_dates: list<string>, absent_days: int, absence_deduction: float
     * }
     */
    public static function calculate(User $employee, string $period, ?OfficeSetting $setting = null): array
    {
        $setting ??= OfficeSetting::current();

        $month = Carbon::createFromFormat('!Y-m', $period);
        $start = $month->copy()->startOfMonth()->toDateString();
        $end = $month->copy()->endOfMonth()->toDateString();

        $base = (float) $employee->salary_base;
        $divisor = $setting->payrollWorkDaysDivisor();
        $dailyRate = $base / $divisor;
        $hoursPerDay = max(60, (int) $setting->required_work_minutes) / 60;
        $hourlyRate = $dailyRate / $hoursPerDay;

        $overtimeCount = OvertimeRequest::query()
            ->where('user_id', $employee->id)
            ->where('status', 'disetujui')
            ->whereBetween('date', [$start, $end])
            ->distinct()
            ->count('date'); // per TANGGAL, bukan per baris: dua pengajuan di tanggal sama tidak dibayar dobel

        $shortage = Attendance::monthlyShortageBlocks($employee->id, $period, $setting);
        $blockHours = $setting->shortageBlockMinutes() / 60;
        $shortageDeduction = round($shortage['blocks'] * $blockHours * $hourlyRate, 2);

        $absentDates = self::absentDates($employee->id, $start, $end);

        return [
            'base' => $base,
            'divisor' => $divisor,
            'daily_rate' => round($dailyRate, 2),
            'hourly_rate' => round($hourlyRate, 2),
            'overtime_count' => $overtimeCount,
            'overtime_amount' => round($overtimeCount * (float) ($employee->flat_overtime_rate ?? 0), 2),
            'shortage' => $shortage,
            'shortage_deduction' => $shortageDeduction,
            'absent_dates' => $absentDates,
            'absent_days' => count($absentDates),
            'absence_deduction' => round(count($absentDates) * $dailyRate, 2),
        ];
    }

    /**
     * Tanggal absen yang benar-benar dipotong: ditandai Absen, tidak ada clock-in
     * pada hari itu, dan tidak tercakup izin/cuti/sakit yang disetujui.
     *
     * @return list<string>
     */
    public static function absentDates(int $userId, string $start, string $end): array
    {
        $marked = AttendanceAbsence::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn($d) => $d->toDateString());

        if ($marked->isEmpty()) {
            return [];
        }

        $clockedIn = Attendance::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->whereNotNull('clock_in_at')
            ->pluck('date')
            ->map(fn($d) => $d->toDateString())
            ->all();

        $leaves = LeaveRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get(['start_date', 'end_date']);

        return $marked
            ->reject(fn(string $date) => in_array($date, $clockedIn, true)
                || $leaves->contains(fn($l) => $l->start_date->toDateString() <= $date && $l->end_date->toDateString() >= $date))
            ->values()
            ->all();
    }
}