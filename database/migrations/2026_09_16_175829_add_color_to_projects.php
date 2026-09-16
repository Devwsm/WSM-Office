<?php

use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_color_to_projects_table
 * ---------------------------------------------------------------------
 * 2026-09-16 — "Warna Project" (temuan audit prototype): form "Create
 * Project" prototype punya field Project Color (hex + color picker),
 * WSM-Office belum punya kolom ini sama sekali — makanya warna project
 * di Timeline Calendar & Work Tracker board SEBELUMNYA hardcoded/
 * deterministik dari `$projectId % count($palette)` di 2 tempat
 * terpisah (Dashboard\Work\CalendarController &
 * Employee\WorkTrackerController), BUKAN warna pilihan user beneran —
 * lihat Project::DEFAULT_COLOR_PALETTE/nextPaletteColor()/colorFor().
 *
 * Backfill di up(): project yang UDAH ADA dari sebelum migration ini
 * dikasih warna dari palet yang SAMA & URUTAN YANG SAMA (id ASC, mod
 * jumlah palet) kayak logic lama di CalendarController — biar warna
 * yang keliatan di kalender/board SEBELUM & SESUDAH migration ini SAMA
 * PERSIS (nggak ada project yang tiba-tiba ganti warna gara-gara
 * migration doang), sebelum user mulai custom warna manual lewat form.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    /**
     * Sengaja di-hardcode di sini juga (bukan baca `Project::DEFAULT_COLOR_PALETTE`
     * yang private) — migration itu snapshot historis, gak boleh
     * bergantung ke konstanta model yang bisa berubah di masa depan;
     * nilainya SENGAJA sama persis urutannya kayak
     * Project::DEFAULT_COLOR_PALETTE saat migration ini ditulis.
     */
    private const PALETTE = [
        '#3558f4',
        '#deb92e',
        '#27c84d',
        '#b4ef4b',
        '#f16c61',
        '#6e95f5',
        '#f3e65c',
    ];

    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('name');
        });

        Project::query()->orderBy('id')->get(['id'])->each(function (Project $project, int $index) {
            $project->newQuery()->whereKey($project->id)->update([
                'color' => self::PALETTE[$index % count(self::PALETTE)],
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};