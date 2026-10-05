<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * budget_fund
 * ---------------------------------------------------------------------
 * 2026-10-04 — "Project Budget" = DANA KESELURUHAN untuk semua project,
 * diinput manual (hanya diedit, tidak ada tambah/hapus) dan bisa diisi
 * walau belum ada project sama sekali. Tujuannya: semua budget item yang
 * diinput sudah punya dana dari awal. Pembandingnya "Budget Allocation"
 * (jumlah budget semua item).
 *
 * Satu baris saja (singleton). Belum ada baris = belum diisi (bukan Rp 0).
 *
 * Soal tabel `project_budget_plans`: versi awal fitur ini sempat membuat
 * tabel anggaran PER PROJECT (salah tafsir). Tabel itu tidak dipakai lagi.
 * Dibuang HANYA kalau kosong; kalau sudah berisi (ada yang sempat mengisi di
 * production) dibiarkan apa adanya supaya tidak ada data yang hilang —
 * migration tidak boleh menghapus data/tabel lama (README 5.11). Di database
 * yang tidak pernah memilikinya, tidak berbuat apa-apa.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_funds', function (Blueprint $table) {
            $table->id();
            $table->decimal('project_budget', 14, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (Schema::hasTable('project_budget_plans') && DB::table('project_budget_plans')->doesntExist()) {
            Schema::drop('project_budget_plans');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_funds');
    }
};