<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_attendance_rules_to_office_settings
 * ---------------------------------------------------------------------
 * 2026-09-29 — dua aturan absensi yang sebelumnya tertanam di kode:
 * - `shortage_block_minutes`: ukuran satu blok "Kurang Jam Kerja" untuk
 *   rekap bulanan & potongan Payroll (dulu tetap 60 menit).
 * - `auto_close_enabled`: sistem menutup otomatis sesi yang lupa pulang
 *   (dulu selalu aktif).
 * Default-nya sama persis dengan perilaku lama (60 menit, aktif), jadi
 * tidak ada yang berubah di production sampai Owner mengubahnya.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('shortage_block_minutes')->default(60);
            $table->boolean('auto_close_enabled')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn(['shortage_block_minutes', 'auto_close_enabled']);
        });
    }
};
