<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Model Attendance
 * ---------------------------------------------------------------------
 * Fase 4: satu baris = satu karyawan, satu hari. Fase 7 (kebijakan WFO
 * v18) nambah: `session_number` (mode Lapangan/Gigs boleh multi-sesi
 * per hari — lihat migration `add_multi_session_to_attendances`),
 * `auto_closed` (checkout dipaksa sistem, bukan manual user), dan
 * accessor buat hitung shortage dalam BLOK 60 menit (bukan cuma
 * status "Kurang Jam Kerja" biner kayak Fase 4).
 *
 * Status kehadiran ("Hadir"/"Terlambat"/dst) TETAP bukan kolom DB —
 * dihitung on-the-fly dari office_settings + jam clock in/out, sama
 * prinsip sejak Fase 4.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'date',
    'session_number',
    'mode',
    'work_context',
    'clock_in_at',
    'clock_in_lat',
    'clock_in_lng',
    'clock_in_accuracy_meters',
    'clock_in_distance_meters',
    'clock_in_within_radius',
    'clock_in_photo',
    'clock_out_at',
    'clock_out_lat',
    'clock_out_lng',
    'clock_out_accuracy_meters',
    'clock_out_distance_meters',
    'clock_out_within_radius',
    'clock_out_photo',
    'auto_closed',
    'original_clock_in_at',
    'original_clock_out_at',
    'corrected_by',
    'corrected_at',
    'correction_note',
])]
class Attendance extends Model
{
    /** Mode yang dianggap "kerja tetap" — cuma boleh 1 sesi per hari & kena window jam normal (09:30-20:00). */
    public const SINGLE_SESSION_MODES = ['kantor', 'wfh'];

    /** Mode yang boleh multi-sesi per hari (Fase 7) — jam kerjanya gak diklem ke window normal. */
    public const MULTI_SESSION_MODES = ['lapangan', 'gigs'];

    public const ALL_MODES = [...self::SINGLE_SESSION_MODES, ...self::MULTI_SESSION_MODES];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_lat' => 'float',
            'clock_in_lng' => 'float',
            'clock_out_lat' => 'float',
            'clock_out_lng' => 'float',
            'clock_in_within_radius' => 'boolean',
            'clock_out_within_radius' => 'boolean',
            'auto_closed' => 'boolean',
            'original_clock_in_at' => 'datetime',
            'original_clock_out_at' => 'datetime',
            'corrected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function corrector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function wasCorrected(): bool
    {
        return $this->corrected_by !== null;
    }

    public function isMultiSessionMode(): bool
    {
        return in_array($this->mode, self::MULTI_SESSION_MODES, true);
    }

    /** Sudah check-in tapi belum check-out, DAN itu hari ini juga. */
    public function isCurrentlyWorking(): bool
    {
        return $this->clock_in_at !== null
            && $this->clock_out_at === null
            && $this->date->isToday();
    }

    /**
     * Check-in ada, check-out kosong, tanggalnya BUKAN hari ini lagi.
     * Fase 7: ini yang jadi target `AttendanceController::reconcileAutoClose()`
     * — kalau kepanggil sebelum sempat di-reconcile (mis. race
     * condition), baris ini masih dianggap "lupa checkout" di UI.
     */
    public function isForgottenCheckout(): bool
    {
        return $this->clock_in_at !== null
            && $this->clock_out_at === null
            && ! $this->date->isToday();
    }

    /**
     * Menit kerja mentah (clock_in ke clock_out, atau ke sekarang kalau
     * masih berjalan hari ini). BELUM diklem ke window normal — dipakai
     * apa adanya buat mode Lapangan/Gigs. Untuk Kantor/WFH pakai
     * `workedMinutesClamped()` yang ngiket ke window 09:30-20:00
     * (kebijakan v18) biar lembur di luar window gak diitung otomatis
     * tanpa approval.
     */
    public function workedMinutes(): ?int
    {
        if (! $this->clock_in_at) {
            return null;
        }

        $end = $this->clock_out_at ?? ($this->date->isToday() ? Carbon::now() : $this->clock_in_at);

        return max(0, (int) $this->clock_in_at->diffInMinutes($end));
    }

    /**
     * Fase 7 — versi diklem ke window kerja normal (`work_start_time`
     * s.d. `normal_end_time` di OfficeSetting), CUMA buat mode
     * Kantor/WFH. Menyamai `recordWorkedMinutes()` di prototype v18:
     * jam sebelum window mulai atau setelah window selesai gak diitung
     * kerja, KECUALI hari itu ada Lembur yang disetujui (dicek di
     * pemanggil lewat `OvertimeRequest::approvedFor()`, bukan di sini,
     * biar model ini gak perlu query tabel lain).
     */
    public function workedMinutesClamped(?OfficeSetting $setting = null): ?int
    {
        if (! $this->clock_in_at || ! $this->isSingleSessionMode()) {
            return $this->workedMinutes();
        }

        $setting ??= OfficeSetting::current();
        $windowStart = $setting->normalStartOn($this->date);
        $windowEnd = $setting->normalEndOn($this->date);

        $end = $this->clock_out_at ?? ($this->date->isToday() ? Carbon::now() : $this->clock_in_at);

        $clampedStart = $this->clock_in_at->max($windowStart);
        $clampedEnd = $end->min($windowEnd);

        if ($clampedEnd->lte($clampedStart)) {
            return 0;
        }

        return (int) $clampedStart->diffInMinutes($clampedEnd);
    }

    private function isSingleSessionMode(): bool
    {
        return in_array($this->mode, self::SINGLE_SESSION_MODES, true);
    }

    public function isLate(?OfficeSetting $setting = null): bool
    {
        if (! $this->clock_in_at) {
            return false;
        }

        $setting ??= OfficeSetting::current();
        $deadline = $this->date->copy()
            ->setTimeFromTimeString($setting->work_start_time)
            ->addMinutes($setting->late_tolerance_minutes);

        return $this->clock_in_at->gt($deadline);
    }

    /**
     * Kekurangan menit kerja HARI INI dibanding `required_work_minutes`,
     * dihitung dari `workedMinutesClamped()` (bukan raw) biar lembur di
     * luar window normal yang BELUM disetujui gak nutupin shortage.
     * Cuma dihitung kalau sesi udah check-out (hari yang masih berjalan
     * belum final). Return 0 kalau `$overtimeApproved` true (dioper
     * dari pemanggil, lihat catatan `workedMinutesClamped()`).
     */
    public function shortageMinutes(bool $overtimeApproved = false, ?OfficeSetting $setting = null): int
    {
        if (! $this->clock_out_at || $overtimeApproved || ! $this->isSingleSessionMode()) {
            return 0;
        }

        $setting ??= OfficeSetting::current();
        $worked = $this->workedMinutesClamped($setting) ?? 0;

        return max(0, $setting->required_work_minutes - $worked);
    }

    /**
     * Fase 7 — akumulasi shortage SEBULAN buat 1 user, dipotong per
     * BLOK 60 menit (sisa menit di bawah 60 dibawa/nggak dipotong bulan
     * ini, sesuai kebijakan v18: "sisa menit di bawah satu blok
     * dibawa ke perhitungan bulan berikutnya" — di sini kita simpan
     * `remainder_minutes` biar pemanggil (mis. Payroll di Fase 12)
     * bisa nerusin ke bulan depan kalau mau, BUKAN otomatis
     * di-carry-over di sini karena itu keputusan payroll, bukan
     * attendance).
     *
     * @return array{total_shortage_minutes:int,blocks:int,remainder_minutes:int}
     */
    public static function monthlyShortageBlocks(int $userId, string $yearMonth, ?OfficeSetting $setting = null): array
    {
        $setting ??= OfficeSetting::current();
        $period = Carbon::createFromFormat('Y-m', $yearMonth)->startOfMonth();

        $rows = static::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$period->copy()->startOfMonth()->toDateString(), $period->copy()->endOfMonth()->toDateString()])
            ->whereNotNull('clock_out_at')
            ->get();

        $approvedDates = OvertimeRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'disetujui')
            ->whereBetween('date', [$period->copy()->startOfMonth()->toDateString(), $period->copy()->endOfMonth()->toDateString()])
            ->pluck('date')
            ->map(fn($d) => $d->toDateString())
            ->all();

        $totalShortage = $rows->sum(function (self $row) use ($setting, $approvedDates) {
            $overtimeApproved = in_array($row->date->toDateString(), $approvedDates, true);

            return $row->shortageMinutes($overtimeApproved, $setting);
        });

        $blocks = intdiv($totalShortage, 60);
        $remainder = $totalShortage % 60;

        return [
            'total_shortage_minutes' => $totalShortage,
            'blocks' => $blocks,
            'remainder_minutes' => $remainder,
        ];
    }

    /**
     * Kunci status internal (buat filter/badge), bukan label tampilan.
     */
    public function statusKey(?OfficeSetting $setting = null): string
    {
        if (! $this->clock_in_at) {
            return 'belum_absen';
        }

        if ($this->isForgottenCheckout()) {
            return 'lupa_absen_pulang';
        }

        if ($this->isCurrentlyWorking()) {
            return 'sedang_bekerja';
        }

        $setting ??= OfficeSetting::current();

        if ($this->isLate($setting)) {
            return 'terlambat';
        }

        if ($this->clock_out_at && $this->isSingleSessionMode() && $this->workedMinutesClamped($setting) < $setting->required_work_minutes) {
            return 'kurang_jam_kerja';
        }

        return 'hadir';
    }

    public function statusLabel(?OfficeSetting $setting = null): string
    {
        return match ($this->statusKey($setting)) {
            'belum_absen' => 'Belum Absen',
            'lupa_absen_pulang' => 'Lupa Absen Pulang',
            'sedang_bekerja' => 'Sedang Bekerja',
            'terlambat' => 'Terlambat',
            'kurang_jam_kerja' => 'Kurang Jam Kerja',
            default => 'Hadir',
        };
    }

    /** Nama class badge yang udah ada di app.css (.badge-wsm-*). */
    public function statusBadgeClass(?OfficeSetting $setting = null): string
    {
        return match ($this->statusKey($setting)) {
            'hadir', 'sedang_bekerja' => 'badge-wsm-green',
            'terlambat', 'kurang_jam_kerja' => 'badge-wsm-yellow',
            'lupa_absen_pulang' => 'badge-wsm-red',
            default => 'badge-wsm-gray',
        };
    }

    /** Semua sesi (Lapangan/Gigs bisa >1) milik 1 user di 1 tanggal, urut sesi. */
    public static function sessionsFor(int $userId, string $date): Collection
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('date', $date)
            ->orderBy('session_number')
            ->get();
    }
}