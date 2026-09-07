<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_royalty_entries_table
 * ---------------------------------------------------------------------
 * Fase 13 — padanan `state.royaltyEntries` (`saveRoyaltyEntry`) di
 * prototype v18. Nempel modul `royalty` (`DashboardAccess::MODULES
 * ['royalty']`) yang slotnya udah ada dari Fase 6a. `period` format
 * `YYYY-MM` (di prototype `<input type="month">`), `status` persis 4
 * opsi yang ada di prototype.
 *
 * CATATAN: prototype v18 juga punya subsistem lebih detail
 * (`state.royaltyFinance` — revenue ledger per-lagu, distribution fee %,
 * statement link, warisan v15) yang SENGAJA TIDAK dimasukin di sini.
 * README Fase 13 cuma nyebut level `royaltyEntries` yang lebih simpel.
 * Kalau nanti breakdown Fase 13 ternyata butuh level detail itu juga,
 * itu nyusul jadi tabel terpisah (`royalty_revenue_statements`), BUKAN
 * expand tabel ini.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('royalty_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->char('period', 7)->nullable(); // format YYYY-MM
            $table->string('source')->nullable();
            $table->enum('status', ['Estimated', 'Reported', 'Ready to Pay', 'Paid'])->default('Estimated');
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('share_pct', 5, 2)->default(100);
            $table->decimal('recoup', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('royalty_entries');
    }
};