<?php

namespace Database\Seeders;

use App\Models\OfficeSetting;
use Illuminate\Database\Seeder;

/**
 * OfficeSettingSeeder
 * ---------------------------------------------------------------------
 * Fase 4 — isi 1 baris office_settings. Koordinat & alamat di bawah ini
 * PLACEHOLDER (titik Monas, Jakarta) — GANTI `latitude`/`longitude`/
 * `address`/`office_name` sesuai lokasi kantor WSM yang sebenarnya
 * sebelum dipakai beneran. Cara paling gampang cari lat/lng: buka lokasi
 * kantor di Google Maps -> klik kanan titiknya -> koordinat langsung
 * ke-copy.
 *
 * Fase 7 (2026-09-06): default `radius_meters` naik dari 150 -> 200,
 * `work_start_time` naik dari 09:00 -> 09:30, dan nambah
 * `normal_end_time` 20:00 — nyamain kebijakan WFO v18 (window kerja
 * normal 09:30–20:00). `geo_attendance_enabled` & `enforce_radius`
 * default true (perilaku persis sama Fase 4, cuma sekarang eksplisit
 * & bisa diubah Owner lewat UI `/owner/pengaturan-kantor`, bukan cuma
 * lewat seeder ini lagi).
 *
 * `updateOrCreate` dipakai (bukan `create`) biar seeder ini aman
 * dijalankan ulang kalau cuma mau update radius/jam kerja, nggak bikin
 * baris duplikat.
 * ---------------------------------------------------------------------
 */
class OfficeSettingSeeder extends Seeder
{
    public function run(): void
    {
        OfficeSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'office_name' => 'WSM Office',
                'address' => 'Jl. Raya Tapos No.43, RT.3/RW.20, Tapos, Kec. Tapos, Kota Depok, Jawa Barat 16457',
                'latitude' => -6.406876513053351,
                'longitude' => 106.88798145029513,
                'radius_meters' => 200,
                'geo_attendance_enabled' => true,
                'enforce_radius' => true,
                'work_start_time' => '09:30:00',
                'normal_end_time' => '20:00:00',
                'late_tolerance_minutes' => 15,
                'required_work_minutes' => 480,
            ]
        );
    }
}