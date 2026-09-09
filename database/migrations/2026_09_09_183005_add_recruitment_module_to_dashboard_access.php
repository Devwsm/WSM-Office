<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * add_recruitment_module_to_dashboard_access
 * ---------------------------------------------------------------------
 * 2026-09-09 — refactor "permission bukan role" (lihat README). Nambah
 * 'recruitment' ke enum `dashboard_access.module`, jadi total 10 modul
 * (lihat App\Models\DashboardAccess::MODULES buat daftar lengkap +
 * alasan kenapa modul ini BUKAN dari prototype v32, sama kayak
 * `legal`/`it` sebelumnya).
 *
 * Sama pola persis migration
 * `add_legal_and_it_modules_to_dashboard_access` — pakai raw SQL
 * `ALTER TABLE ... MODIFY` karena Schema builder Laravel gak bisa ubah
 * enum existing tanpa doctrine/dbal.
 *
 * PENTING: jangan lupa `App\Models\DashboardAccess::MODULES` (sudah
 * diupdate bareng migration ini) — itu bagian kode PHP, gak otomatis
 * ikut ke sini.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE dashboard_access MODIFY module ENUM(
            'work',
            'budget',
            'royalty',
            'kpi',
            'people',
            'contracts',
            'payroll',
            'legal',
            'it',
            'recruitment'
        ) NOT NULL");
    }

    public function down(): void
    {
        // Turun balik ke 9 modul lama. Kalau udah ada baris dengan
        // module 'recruitment' pas rollback, MySQL bakal nolak (data
        // gak valid buat enum baru) — hapus dulu baris itu manual
        // sebelum rollback kalau kejadian.
        DB::statement("ALTER TABLE dashboard_access MODIFY module ENUM(
            'work',
            'budget',
            'royalty',
            'kpi',
            'people',
            'contracts',
            'payroll',
            'legal',
            'it'
        ) NOT NULL");
    }
};