<?php

namespace App\Support;

/**
 * Geo
 * ---------------------------------------------------------------------
 * Helper hitung jarak antar 2 titik koordinat pakai formula Haversine.
 * Dipakai di server (AttendanceController) buat hitung ulang jarak dari
 * kantor secara independen — nggak percaya begitu saja angka jarak yang
 * dikirim dari JS, karena itu bisa dimanipulasi user.
 * ---------------------------------------------------------------------
 */
class Geo
{
    /** Radius bumi rata-rata dalam meter. */
    private const EARTH_RADIUS_METERS = 6371000;

    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): int
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round(self::EARTH_RADIUS_METERS * $c);
    }
}