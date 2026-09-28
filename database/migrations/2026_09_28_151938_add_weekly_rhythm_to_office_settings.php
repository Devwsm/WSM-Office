<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_weekly_rhythm_to_office_settings
 * ---------------------------------------------------------------------
 * 2026-09-28 — Weekly Rhythm (Alignment & Planning dst.) sebelumnya
 * hardcoded di 3 controller (Dashboard\Work\CalendarController,
 * Employee\WorkTrackerController, Owner\DashboardController). Prototype
 * menyimpannya di `state.settings.weeklyRhythm` dan bisa diubah lewat
 * "Weekly Rhythm Settings" — di sini disimpan sebagai JSON di
 * `office_settings` (tabel singleton, sama alasan dengan kolom warna
 * aksen & shortage_deduction_rate). NULL = pakai default bawaan
 * (OfficeSetting::DEFAULT_WEEKLY_RHYTHM), jadi aman dijalankan di
 * production: kalender tetap tampil sama sampai ada yang mengubahnya.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->json('weekly_rhythm')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn('weekly_rhythm');
        });
    }
};