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
                'radius_meters' => 150,
                'work_start_time' => '09:00:00',
                'late_tolerance_minutes' => 15,
                'required_work_minutes' => 480,
            ]
        );
    }
}