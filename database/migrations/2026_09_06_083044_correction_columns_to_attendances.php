<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_correction_columns_to_attendances_table
 * ---------------------------------------------------------------------
 * Fase 5 — koreksi absen manual (Manajer/Owner) yang sengaja ditunda
 * dari Fase 4. `original_clock_in_at`/`original_clock_out_at` cuma
 * kesisi SEKALI, saat koreksi PERTAMA kali dilakukan pada baris itu
 * (lihat Attendance\RecapController::correct()) — biar karyawan tetap
 * bisa lihat "jam aslinya berapa" walau dikoreksi berkali-kali,
 * bukan cuma versi terakhir sebelum koreksi terbaru.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dateTime('original_clock_in_at')->nullable()->after('clock_in_photo');
            $table->dateTime('original_clock_out_at')->nullable()->after('clock_out_photo');
            $table->foreignId('corrected_by')->nullable()->after('original_clock_out_at')->constrained('users')->nullOnDelete();
            $table->dateTime('corrected_at')->nullable()->after('corrected_by');
            $table->text('correction_note')->nullable()->after('corrected_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrected_by');
            $table->dropColumn(['original_clock_in_at', 'original_clock_out_at', 'corrected_at', 'correction_note']);
        });
    }
};