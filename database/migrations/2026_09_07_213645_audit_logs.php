<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_audit_logs_table
 * ---------------------------------------------------------------------
 * Fase 15 (BARU) — padanan `state.auditLog` (`auditV18()`) di prototype
 * v18. `actor_id` nullable + `actor_label` fallback string — prototype
 * kadang nyimpen actor sebagai 'System' (bukan user beneran, mis. auto
 * pas mengumpulkan attendance auto-close).
 *
 * SENGAJA cuma `created_at` (bukan pakai `$table->timestamps()`) — log
 * gak pernah diedit, jadi `updated_at` gak kepake sama sekali.
 *
 * Prototype auto-prune ke 500 baris terakhir (`state.auditLog.slice
 * (0,500)`, batasan localStorage). DI SINI SENGAJA GAK DI-PRUNE — tabel
 * beneran boleh nambah terus, ditampilin dengan pagination. Retensi/
 * archiving baru dipikirin kalau tabelnya udah kegedean beneran, bukan
 * dibatasi dari awal kayak prototype.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label')->nullable();
            $table->string('action');
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};