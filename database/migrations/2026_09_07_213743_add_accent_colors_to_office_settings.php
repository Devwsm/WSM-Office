<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_accent_colors_to_office_settings_table
 * ---------------------------------------------------------------------
 * Fase 16 (CEO Dashboard IA Restructure & Settings) — padanan
 * `state.settings.ceoAccent`/`workAccent` (`saveThemeV18`) di prototype
 * v18. Ini SATU-SATUNYA bagian Fase 16 yang butuh kolom baru — sisanya
 * (sidebar 7 grup + badge LIMITED + sidebar scroll independen) murni
 * kerjaan Blade/CSS, gak nyentuh database.
 *
 * Ditaruh di `office_settings` (bukan tabel `settings` baru) karena ini
 * tabel singleton yang sama yang udah nampung "pengaturan global
 * sistem" lain (geo, jam kerja) — nambah 1 tabel baru cuma buat 2 kolom
 * warna kerasa berlebihan.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->string('ceo_accent_color', 7)->default('#111111')->after('required_work_minutes');
            $table->string('work_accent_color', 7)->default('#3558f4')->after('ceo_accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn(['ceo_accent_color', 'work_accent_color']);
        });
    }
};