<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_v18_policy_columns_to_office_settings
 * ---------------------------------------------------------------------
 * Fase 7 — nambah kolom yang dibutuhin kebijakan WFO v18 (lihat README,
 * bagian "Rombak Rencana"), tanpa hapus kolom lama (`work_start_time`
 * tetap dipakai sebagai jam MULAI window normal WFO, sekarang dipasangi
 * `normal_end_time` sebagai jam SELESAI-nya, default 09:30–20:00 persis
 * kebijakan v18 — sebelumnya cuma ada `work_start_time` buat threshold
 * telat doang, belum ada konsep "window kerja normal" yang dipakai buat
 * auto-close & hitung shortage).
 *
 * `geo_attendance_enabled` & `enforce_radius` — sebelumnya toggle ini
 * gak ada sama sekali, radius selalu dicek keras. Sekarang Owner/HR
 * bisa matiin pengecekan geo total (mis. lagi ada acara di luar kantor
 * seminggu) atau matiin cuma enforce radius-nya doang (geo tetep
 * dicatat buat informasi, tapi gak bikin `within_radius=false` jadi
 * masalah — walau kode kita dari awal emang gak pernah nge-block absen
 * di luar radius, cuma nandain doang, jadi toggle ini lebih ke
 * "informasi radius mau ditampilin atau di-skip aja").
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->time('normal_end_time')->default('20:00:00')->after('work_start_time');
            $table->boolean('geo_attendance_enabled')->default(true)->after('radius_meters');
            $table->boolean('enforce_radius')->default(true)->after('geo_attendance_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn(['normal_end_time', 'geo_attendance_enabled', 'enforce_radius']);
        });
    }
};