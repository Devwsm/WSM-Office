<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Model OfficeSetting
 * ---------------------------------------------------------------------
 * Singleton (selalu 1 baris) — titik lokasi kantor + radius toleransi +
 * aturan jam kerja, dipakai buat validasi absen (Fase 4) & kebijakan
 * WFO v18 (Fase 7 — jam normal 09:30–20:00, auto-close, toggle geo).
 * Diisi lewat OfficeSettingSeeder; UI edit dari Owner sejak Fase 7
 * (`Owner\OfficeSettingController`) — sebelumnya cuma lewat seeder.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'office_name',
    'address',
    'latitude',
    'longitude',
    'radius_meters',
    'geo_attendance_enabled',
    'enforce_radius',
    'work_start_time',
    'normal_end_time',
    'late_tolerance_minutes',
    'required_work_minutes',
])]
class OfficeSetting extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geo_attendance_enabled' => 'boolean',
            'enforce_radius' => 'boolean',
        ];
    }

    /**
     * Ambil baris singleton. Kalau belum pernah di-seed, fallback ke
     * nilai placeholder biar fitur absen nggak crash — radius 0 &
     * geo_attendance_enabled false biar kentara jelas belum
     * dikonfigurasi (bukan diam-diam nganggep udah settingan asli).
     */
    public static function current(): self
    {
        return static::query()->first() ?? new self([
            'office_name' => 'WSM Office (belum dikonfigurasi)',
            'latitude' => 0,
            'longitude' => 0,
            'radius_meters' => 200,
            'geo_attendance_enabled' => false,
            'enforce_radius' => true,
            'work_start_time' => '09:30:00',
            'normal_end_time' => '20:00:00',
            'late_tolerance_minutes' => 15,
            'required_work_minutes' => 480,
        ]);
    }

    /** Jam mulai window kerja normal hari ini, sebagai Carbon (buat dipasangin ke tanggal tertentu). */
    public function normalStartOn(Carbon|string $date): Carbon
    {
        return Carbon::parse($date)->setTimeFromTimeString($this->work_start_time);
    }

    /** Jam selesai window kerja normal hari ini — dipakai buat auto-close & hitung shortage (Fase 7). */
    public function normalEndOn(Carbon|string $date): Carbon
    {
        return Carbon::parse($date)->setTimeFromTimeString($this->normal_end_time);
    }

    /** Total menit window kerja normal (dari work_start_time ke normal_end_time), buat referensi shortage. */
    public function normalWindowMinutes(): int
    {
        $start = Carbon::parse($this->work_start_time);
        $end = Carbon::parse($this->normal_end_time);

        return max(0, $start->diffInMinutes($end));
    }
}