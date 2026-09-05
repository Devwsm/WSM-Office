<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Attendance
 * ---------------------------------------------------------------------
 * Satu baris = satu karyawan, satu hari (Fase 4). Status kehadiran
 * ("Hadir"/"Terlambat"/dst) SENGAJA bukan kolom DB — dihitung di sini
 * dari office_settings + jam clock in/out biar selalu akurat walau
 * office_settings di-update belakangan. Lihat catatan lengkap di
 * migration `create_attendances_table`.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'user_id',
    'date',
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
])]
class Attendance extends Model
{
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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sudah check-in tapi belum check-out, DAN itu hari ini juga. */
    public function isCurrentlyWorking(): bool
    {
        return $this->clock_in_at !== null
            && $this->clock_out_at === null
            && $this->date->isToday();
    }

    /** Check-in ada, check-out kosong, tapi tanggalnya BUKAN hari ini lagi — lupa absen pulang. */
    public function isForgottenCheckout(): bool
    {
        return $this->clock_in_at !== null
            && $this->clock_out_at === null
            && ! $this->date->isToday();
    }

    public function workedMinutes(): ?int
    {
        if (! $this->clock_in_at) {
            return null;
        }

        $end = $this->clock_out_at ?? ($this->date->isToday() ? Carbon::now() : $this->clock_in_at);

        return max(0, (int) $this->clock_in_at->diffInMinutes($end));
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

        // Sudah check-out (atau hari lampau yang lengkap).
        $setting ??= OfficeSetting::current();

        if ($this->isLate($setting)) {
            return 'terlambat';
        }

        if ($this->clock_out_at && $this->workedMinutes() < $setting->required_work_minutes) {
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
}