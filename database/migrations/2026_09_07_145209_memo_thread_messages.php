<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_memo_thread_messages_table
 * ---------------------------------------------------------------------
 * Fase 8 — Memo Forum. Padanan `state.memoThreads` di prototype v18:
 * SATU thread flat per memo, dibagi bareng semua orang yang bisa lihat
 * memo itu (bukan channel privat per-karyawan — sempat ketuker sama
 * fungsi `memoReplies` yang lebih lama/sudah ditinggalkan di prototype,
 * lihat README kalau perlu histori penjelasannya).
 *
 * `read_by_management_at` SENGAJA beda dari desain prototype: di
 * prototype, badge unread di sidebar CEO itung SEMUA pesan employee
 * dari awal waktu (`state.memoThreads.filter(authorType==='employee').
 * length`) — gak pernah berkurang walau udah dibaca, itu bug/kelalaian
 * di prototype-nya sendiri (badge "unread" yang gak pernah nge-reset
 * bukan UX yang benar). Di sini badge-nya beneran ngitung yang BELUM
 * dibaca manajemen (kolom ini di-set pas modul Work dibuka manage-level
 * user) — lihat MemoController::index().
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_thread_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('read_by_management_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_thread_messages');
    }
};