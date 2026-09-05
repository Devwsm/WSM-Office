<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Model OfficeSetting
 * ---------------------------------------------------------------------
 * Singleton (selalu 1 baris) — titik lokasi kantor + radius toleransi +
 * aturan jam kerja standar, dipakai buat validasi absen (Fase 4). Diisi
 * lewat OfficeSettingSeeder; UI edit dari Owner baru Fase 12.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'office_name',
    'address',
    'latitude',
    'longitude',
    'radius_meters',
    'work_start_time',
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
        ];
    }

    /**
     * Ambil baris singleton. Kalau belum pernah di-seed (mis. lupa
     * jalanin OfficeSettingSeeder), fallback ke nilai placeholder biar
     * fitur absen nggak crash — tapi radius 0 supaya kentara jelas belum
     * dikonfigurasi (semua absen bakal keluar "di luar radius").
     */
    public static function current(): self
    {
        return static::query()->first() ?? new self([
            'office_name' => 'WSM Office (belum dikonfigurasi)',
            'latitude' => 0,
            'longitude' => 0,
            'radius_meters' => 0,
            'work_start_time' => '09:00:00',
            'late_tolerance_minutes' => 15,
            'required_work_minutes' => 480,
        ]);
    }
}