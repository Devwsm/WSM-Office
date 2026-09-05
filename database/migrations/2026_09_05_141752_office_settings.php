<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_office_settings_table
 * ---------------------------------------------------------------------
 * Fase 4 — tabel singleton (selalu cuma 1 baris, pola sama seperti
 * `profile` di WS-talent-website) buat nyimpen titik lokasi kantor,
 * radius toleransi absen, dan aturan jam kerja standar. Diisi lewat
 * OfficeSettingSeeder dengan nilai placeholder — Owner/dev ganti sendiri
 * (lat/lng/radius/jam) lewat seeder untuk sekarang; UI Settings baru
 * masuk Fase 12.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_settings', function (Blueprint $table) {
            $table->id();
            $table->string('office_name')->default('WSM Office');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            // Radius toleransi absen dari titik kantor, dalam meter.
            $table->unsignedInteger('radius_meters')->default(150);
            // Jam masuk standar + toleransi telat, dipakai buat status "Terlambat".
            $table->time('work_start_time')->default('09:00:00');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15);
            // Jam kerja wajib per hari (menit), dipakai buat status "Kurang Jam Kerja".
            $table->unsignedSmallInteger('required_work_minutes')->default(480);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_settings');
    }
};