<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payroll: potongan hari absen + jejak buka-kembali payroll final.
 * Record lama otomatis absent_days = 0 / absence_deduction = 0 dan
 * work_days_divisor NULL (dibaca sebagai 22 saat ditampilkan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->unsignedSmallInteger('absent_days')->default(0)->after('shortage_deduction');
            $table->decimal('absence_deduction', 12, 2)->default(0)->after('absent_days');
            $table->unsignedTinyInteger('work_days_divisor')->nullable()->after('absence_deduction');
            $table->foreignId('reopened_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable()->after('reopened_by');
            $table->string('reopen_reason', 500)->nullable()->after('reopened_at');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reopened_by');
            $table->dropColumn(['absent_days', 'absence_deduction', 'work_days_divisor', 'reopened_at', 'reopen_reason']);
        });
    }
};