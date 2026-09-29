<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "Absen (A)" manual per karyawan per tanggal — padanan
 * `manualStatus === 'Absen (A)'` di prototype v32. Hanya hari yang
 * ditandai di sini yang dipotong payroll (keputusan opsi B); hari tanpa
 * clock-in yang tidak ditandai TIDAK dipotong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('note', 255);
            $table->foreignId('marked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_absences');
    }
};