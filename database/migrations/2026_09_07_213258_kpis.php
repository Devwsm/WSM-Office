<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_kpis_table
 * ---------------------------------------------------------------------
 * Fase 10 — padanan `state.kpis` (`saveKpi`) di prototype v18, nempel
 * modul `kpi` yang slotnya udah ada dari Fase 6a (`DashboardAccess::
 * MODULES['kpi']`).
 *
 * `period` SENGAJA `string` bebas (mis. "Q3 2026"), BUKAN kolom
 * tanggal — prototype nyimpennya teks bebas juga, gak divalidasi
 * format tertentu.
 *
 * `status` ada 2 opsi TAMBAHAN di luar prototype (`Completed`,
 * `Archived`) — prototype cuma pernah nulis `'Active'` literal pas
 * `saveKpi()` (gak ada UI buat ubah status KPI ke nilai lain). Kita
 * tambahin biar KPI yang udah kelar/gak relevan lagi bisa diarsipkan
 * tanpa perlu dihapus dari histori.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('period');
            $table->decimal('target', 12, 2)->default(0);
            $table->decimal('current', 12, 2)->default(0);
            $table->string('unit')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['Active', 'Completed', 'Archived'])->default('Active');
            $table->text('owner_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};