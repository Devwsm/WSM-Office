<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_legal_and_it_modules_to_dashboard_access_table
 * ---------------------------------------------------------------------
 * Fase 14 & 15 — nambah 'legal' & 'it' ke enum `dashboard_access.module`
 * (lihat `create_dashboard_access_table` buat 7 modul awal Fase 6a).
 * Sekarang total 9 modul, persis sidebar CEO v18: People (pakai
 * `people`, bukan modul access baru), Work Control, Finance (`budget`),
 * Royalty, HR Admin (`kpi`/`contracts`/`payroll`), Legal (BARU), IT
 * (BARU).
 *
 * Pakai `Schema::table()->enum()->change()` bawaan Laravel (11+, tanpa
 * doctrine/dbal). Di MySQL/MariaDB hasilnya sama dengan
 * `ALTER TABLE ... MODIFY module ENUM(...) NOT NULL`; di SQLite (dipakai
 * suite tes, lihat phpunit.xml) tabel di-rebuild. Sebelumnya migration
 * ini memakai raw `ALTER TABLE ... MODIFY` yang bikin SEMUA tes gagal di
 * SQLite. Tetap aman buat shared hosting cPanel tanpa terminal (impor
 * SQL hasil `migrate` lokal).
 *
 * PENTING: jangan lupa update juga
 * `App\Models\DashboardAccess::MODULES` (tambah entri 'legal' & 'it'
 * dengan label/desc/icon) SETELAH migration ini jalan — itu bagian
 * kode PHP, gak otomatis ikut kesini.
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
        ]);
    }

    public function down(): void
    {
        // Turun balik ke 7 modul lama. Kalau udah ada baris dengan
        // module 'legal'/'it' pas rollback, MySQL bakal nolak (data
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