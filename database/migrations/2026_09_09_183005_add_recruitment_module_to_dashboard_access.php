<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
 * `add_legal_and_it_modules_to_dashboard_access` — pakai
 * `Schema::table()->enum()->change()` (portable MySQL/MariaDB + SQLite),
 * bukan raw `ALTER TABLE ... MODIFY`.
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
        $this->setModules([
            'work',
            'budget',
            'royalty',
            'kpi',
            'people',
            'contracts',
            'payroll',
            'legal',
            'it',
            'recruitment',
        ]);
    }

    public function down(): void
    {
        // Turun balik ke 9 modul lama. Kalau udah ada baris dengan
        // module 'recruitment' pas rollback, MySQL bakal nolak (data
        // gak valid buat enum baru) — hapus dulu baris itu manual
        // sebelum rollback kalau kejadian.
        $this->setModules([
            'work',
            'budget',
            'royalty',
            'kpi',
            'people',
            'contracts',
            'payroll',
            'legal',
            'it',
        ]);
    }

    /**
     * Ganti daftar nilai enum `dashboard_access.module`. `->change()`
     * bawaan Laravel (11+) jalan di MySQL/MariaDB (`ALTER TABLE ... MODIFY`)
     * maupun SQLite (rebuild tabel), jadi suite tes yang memakai SQLite
     * `:memory:` (lihat phpunit.xml) bisa menjalankan migrasi ini.
     *
     * @param  list<string>  $modules
     */
    private function setModules(array $modules): void
    {
        Schema::table('dashboard_access', function (Blueprint $table) use ($modules) {
            $table->enum('module', $modules)->change();
        });
    }
};