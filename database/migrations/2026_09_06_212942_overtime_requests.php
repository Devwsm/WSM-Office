<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_overtime_requests_table
 * ---------------------------------------------------------------------
 * Fase 7 — Lembur (WFO overtime) WAJIB request & approval dulu sebelum
 * dianggap sah, sama persis polanya kayak `leave_requests` Fase 5
 * (pending -> disetujui/ditolak, bisa dibatalkan). SENGAJA tabel
 * terpisah dari `leave_requests` walau state-nya mirip banget — Lembur
 * itu izin KERJA LEBIH LAMA (bukan izin gak masuk), scope-nya juga
 * beda: `unique(user_id,date)` di sini artinya cuma boleh 1 pengajuan
 * lembur aktif per orang per tanggal (samain kesepakatan prototype),
 * bukan rentang tanggal kayak cuti.
 *
 * PENTING (keputusan 2026-09-06, lihat README bagian "Rombak
 * Rencana"): tabel ini SENGAJA belum punya kolom nominal rupiah
 * (`overtime_flat_rate`) — itu ditunda ke Fase 12 (Payroll) bareng
 * field `salary`/`daily_hours` di tabel `users`. Fase 7 cuma nyimpen
 * STATUS approval-nya doang, dipakai `Attendance` buat nentuin boleh
 * gak auto-close di-skip / shortage di-skip hari itu — bukan buat
 * hitung uang.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->text('reason');

            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'dibatalkan'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};