<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan payroll (2026-09-29): pembagi hari kerja sebulan buat tarif harian
 * (gaji pokok ÷ pembagi). Default 22, sama dengan `workDaysDivisor` prototype v32.
 * Kolom `shortage_deduction_rate` SENGAJA tidak dihapus (data lama aman) tapi
 * sudah tidak dipakai: potongan kurang jam sekarang diturunkan dari gaji.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('payroll_work_days_divisor')->default(22);
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn('payroll_work_days_divisor');
        });
    }
};