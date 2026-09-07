<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_payroll_fields_to_users_table
 * ---------------------------------------------------------------------
 * Fase 12 — field yang SENGAJA ditunda dari Fase 7 (lihat keputusan
 * 2026-09-06 di README): `Gaji Pokok`, `Target Jam/Hari`, `Flat
 * Overtime Rate` di form Karyawan (padanan `oeSalary`/`oeHours`/
 * `oeOvertimeFlat` di prototype). Baru ditambah sekarang (Fase 12)
 * karena baru di sini beneran kepake buat generate `payroll_records`.
 *
 * `flat_overtime_rate` inilah rate yang dipakai buat ngitung
 * `overtime_amount` di `payroll_records` dari Lembur yang udah
 * disetujui (status doang, Fase 7) — bukan dihitung per jam durasi,
 * sesuai kebijakan v18.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('salary_base', 12, 2)->nullable()->after('annual_leave_entitlement');
            $table->unsignedInteger('target_hours_per_day')->nullable()->after('salary_base');
            $table->decimal('flat_overtime_rate', 12, 2)->nullable()->after('target_hours_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['salary_base', 'target_hours_per_day', 'flat_overtime_rate']);
        });
    }
};