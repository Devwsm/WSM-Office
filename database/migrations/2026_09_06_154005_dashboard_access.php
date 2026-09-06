<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_dashboard_access_table
 * ---------------------------------------------------------------------
 * Fase 6a — fondasi sistem akses per-user per-modul, ngikutin persis
 * konsep "Dashboard Access" di prototype v13 (absensi_wsm): Owner
 * assign level None/View/Manage per modul ke tiap karyawan, terpisah
 * dari jabatan (`users.role`).
 *
 * SENGAJA TIDAK dipakai buat Fase 4 (rekap absensi) & Fase 5 (approval
 * izin/cuti) — dua fitur itu tetap role-based apa adanya
 * (middleware `role:...`), sesuai keputusan waktu breakdown Fase 6.
 * Tabel ini cuma buat modul BARU mulai Fase 6b ke atas (Work Control,
 * Project Budgeting, Royalty, KPI & Performance, People & Leave,
 * Contract Monitoring, Payroll Overview — 7 modul persis dari
 * prototype, lihat App\Models\DashboardAccess::MODULES).
 *
 * Owner SENGAJA TIDAK dikasih baris di tabel ini — akses Owner selalu
 * 'manage' di semua modul, dihitung di kode
 * (User::accessLevel()/canManage()), bukan disimpan sebagai data.
 * Jadi Owner baru yang dibuat kapanpun otomatis full-access tanpa perlu
 * seed ulang.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('module', [
                'work',      // Work Control — project, tracker, timeline, MoM, memo
                'budget',    // Project Budgeting
                'royalty',   // Royalty Dashboard
                'kpi',       // KPI & Performance
                'people',    // People & Leave
                'contracts', // Contract Monitoring
                'payroll',   // Payroll Overview
            ]);
            $table->enum('level', ['view', 'manage'])->default('view');
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // 1 baris per user per modul. Kalau levelnya 'none', barisnya
            // dihapus (bukan disimpan dengan level='none') — lihat
            // DashboardAccessController::update().
            $table->unique(['user_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_access');
    }
};