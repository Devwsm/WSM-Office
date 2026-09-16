<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_attendance_correction_requests_table
 * ---------------------------------------------------------------------
 * 2026-09-15 — "Koreksi Presensi" jadi jenis pengajuan resmi karyawan
 * sendiri (padanan "Gerry · Koreksi Presensi" di prototype), gabung ke
 * kluster Izin/Cuti (Fase 5) & Lembur (Fase 7) — status flow, kolom
 * approve/reject/cancel SENGAJA disamain persis `leave_requests`/
 * `overtime_requests` biar controller Approval-nya bisa pakai
 * canDecide() & FormRequest (Reject/Cancel) yang SAMA, gak bikin
 * duplikat.
 *
 * BEDA dari koreksi manual Manajer/Owner yang udah ada
 * (`Attendance::original_clock_in_at` dkk, RecapController::correct())
 * — itu manajer LANGSUNG edit attendance yang udah ada, ini karyawan
 * MENGAJUKAN dulu, baru diterapkan ke tabel `attendances` kalau
 * disetujui (lihat Approval\AttendanceCorrectionRequestController::approve()).
 * Begitu disetujui, request ini jadi "resep" buat `RecapController::correct()`-
 * style update, dijalanin otomatis — bukan alur baru dari nol.
 *
 * `requested_mode` cuma kepake kalau tanggal itu BELUM ada baris
 * `attendances` sama sekali (karyawan bener-bener lupa buka app,
 * belum ada sesi buat dikoreksi) — kalau udah ada baris, mode yang
 * ada gak diubah, cuma jam yang dikoreksi (sama kesepakatan
 * `CorrectAttendanceRequest`: "cuma edit jam, bukan override status").
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->string('requested_clock_in', 5)->nullable();
            $table->string('requested_clock_out', 5)->nullable();
            $table->enum('requested_mode', ['kantor', 'wfh', 'lapangan', 'gigs'])->default('kantor');
            $table->text('reason');
            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'dibatalkan'])->default('pending');

            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            // Attendance yang beneran kena update pas disetujui (nullable
            // kalau approve-nya bikin baris attendance baru dari nol).
            $table->foreignId('applied_attendance_id')->nullable()->constrained('attendances')->nullOnDelete();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};