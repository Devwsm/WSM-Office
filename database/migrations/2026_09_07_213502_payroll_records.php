<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_payroll_records_table
 * ---------------------------------------------------------------------
 * Fase 12 — DEVIASI DISENGAJA dari prototype: prototype cuma nampilin
 * payroll sebagai angka "estimate" yang dihitung ulang tiap buka
 * halaman (`thp` di `ownerDashboard()`), gak pernah disimpan permanen.
 * Di web resmi kita simpen histori per bulan (`period`, format
 * `YYYY-MM`) biar Owner tetap bisa lihat payroll bulan lalu APA ADANYA
 * walau `users.salary_base`/`flat_overtime_rate`/aturan shortage
 * direvisi belakangan — makanya `base_salary` di sini adalah SALINAN
 * nilai pas digenerate, bukan kolom yang ikut berubah kalau
 * `users.salary_base` diedit setelahnya.
 *
 * `overtime_amount` dihitung dari jumlah OvertimeRequest berstatus
 * disetujui bulan itu × `users.flat_overtime_rate` (bukan per jam).
 * `shortage_deduction` dihitung dari `Attendance::monthlyShortageBlocks()`
 * (Fase 7) — rate rupiah per blok 60 menit perlu diputusin pas
 * breakdown Fase 12 (belum ada acuan di prototype v18 buat angka
 * pastinya, cuma kebijakan "dipotong per blok").
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->char('period', 7); // format YYYY-MM
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('shortage_deduction', 12, 2)->default(0);
            $table->decimal('other_adjustment', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};