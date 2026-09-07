<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_memo_reads_table
 * ---------------------------------------------------------------------
 * Fase 8 — Memo Forum. Padanan `memoInboxState` di prototype v18: 1
 * baris = status baca & sembunyi 1 user buat 1 memo. Gak ada baris =
 * belum dibaca & belum disembunyiin (default), sama kayak
 * `dashboard_access` (gak ada baris = 'none') — biar konsisten pola
 * "absennya baris = default", bukan nyimpen semua kombinasi user×memo
 * dari awal.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('hidden_at')->nullable();
            $table->timestamps();

            $table->unique(['memo_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_reads');
    }
};