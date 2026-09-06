<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_leave_requests_table
 * ---------------------------------------------------------------------
 * Fase 5 — pengajuan izin/cuti karyawan. `work_days` dihitung pas
 * submit (hari kerja Senin-Jumat di rentang tanggal, lihat
 * LeaveRequest::countWorkDays()) — disimpan sebagai kolom (bukan
 * dihitung ulang tiap saat) karena dipakai buat potong saldo cuti
 * tahunan dan aturan hari kerja bisa berubah di masa depan (CMS), jadi
 * angka yang KEPAKAI harus tetap sama dengan yang berlaku pas diajukan.
 *
 * Approval TIDAK ditentukan di awal (nggak ada kolom "assigned_to") —
 * siapa yang boleh approve dihitung on-the-fly dari manager_id (lihat
 * Approval\LeaveRequestController::canDecide()), karena Owner bisa
 * approve/gantikan siapa aja kapan aja (kesepakatan Fase 5).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['cuti_tahunan', 'izin_sakit', 'izin_pribadi', 'lainnya']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('work_days');
            $table->text('reason');
            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'dibatalkan'])->default('pending');

            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            // Diisi kalau ditolak (wajib) — opsional kalau disetujui.
            $table->text('decision_note')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};