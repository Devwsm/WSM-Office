<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
 * Laravel Schema builder gak bisa ubah nilai enum existing secara
 * langsung (butuh doctrine/dbal buat `change()`), jadi pakai raw SQL
 * `ALTER TABLE ... MODIFY` — pola umum buat kasus ini di MySQL, aman
 * buat shared hosting cPanel yang gak ada akses terminal (migration
 * tetap jalan lewat `php artisan migrate` biasa).
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

    public function down(): void
    {
        // Turun balik ke 7 modul lama. Kalau udah ada baris dengan
        // module 'legal'/'it' pas rollback, MySQL bakal nolak (data
        // gak valid buat enum baru) — hapus dulu baris itu manual
        // sebelum rollback kalau kejadian.
        DB::statement("ALTER TABLE dashboard_access MODIFY module ENUM(
            'work',
            'budget',
            'royalty',
            'kpi',
            'people',
            'contracts',
            'payroll'
        ) NOT NULL");
    }
};