<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_attendances_table
 * ---------------------------------------------------------------------
 * Fase 4 — satu baris per karyawan per hari (unique user_id+date). Status
 * (Hadir/Terlambat/Kurang Jam Kerja/Sedang Bekerja/Lupa Absen Pulang)
 * SENGAJA tidak disimpan sebagai kolom — dihitung on-the-fly lewat
 * accessor di model `Attendance` dari office_settings + jam clock in/out,
 * biar nggak ada data basi kalau office_settings berubah nanti. Baris
 * "Alpha" (karyawan yang sama sekali nggak absen) juga nggak disimpan di
 * sini — itu dihitung di halaman rekap dengan bandingkan daftar karyawan
 * vs baris yang ada untuk tanggal tsb, karena belum ada cron di hosting
 * ini buat auto-generate baris kosong tiap hari.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');

            // 'kantor' -> radius dicek. 'wfh' -> radius di-skip (kesepakatan Fase 4).
            $table->enum('mode', ['kantor', 'wfh'])->default('kantor');
            $table->string('work_context')->nullable();

            $table->dateTime('clock_in_at')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_in_accuracy_meters')->nullable();
            $table->unsignedInteger('clock_in_distance_meters')->nullable();
            $table->boolean('clock_in_within_radius')->nullable();
            $table->string('clock_in_photo')->nullable();

            $table->dateTime('clock_out_at')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();
            $table->unsignedInteger('clock_out_accuracy_meters')->nullable();
            $table->unsignedInteger('clock_out_distance_meters')->nullable();
            $table->boolean('clock_out_within_radius')->nullable();
            $table->string('clock_out_photo')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};