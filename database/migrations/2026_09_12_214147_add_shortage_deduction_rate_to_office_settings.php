<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_shortage_deduction_rate_to_office_settings_table
 * ---------------------------------------------------------------------
 * Fase 12 (Payroll) — keputusan yang sengaja ditunda sejak migration
 * `create_payroll_records_table` (Fase 7/12 prep): "rate rupiah per
 * blok 60 menit perlu diputusin pas breakdown Fase 12 (belum ada acuan
 * di prototype v18 buat angka pastinya, cuma kebijakan 'dipotong per
 * blok')".
 *
 * Ditaruh di `office_settings` (bukan kolom per-user di `users`) karena
 * ini KEBIJAKAN PERUSAHAAN yang sama buat semua karyawan — beda dari
 * `flat_overtime_rate` yang emang didesain per-orang (`oeOvertimeFlat`
 * prototype, tiap karyawan bisa beda). Owner atur nilainya lewat form
 * Pengaturan Kantor yang sama (Fase 7), bukan bikin tabel `settings`
 * baru — sama alasan `ceo_accent_color`/`work_accent_color` numpang di
 * sini juga.
 *
 * Dipakai `Dashboard\Payroll\PayrollController::generate()`:
 * `shortage_deduction` = `Attendance::monthlyShortageBlocks()['blocks']`
 * (Fase 7) × rate ini.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->decimal('shortage_deduction_rate', 12, 2)->default(0)->after('work_accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn('shortage_deduction_rate');
        });
    }
};