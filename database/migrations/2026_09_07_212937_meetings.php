<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_meetings_table
 * ---------------------------------------------------------------------
 * Fase 9 — Minutes of Meeting, padanan `state.meetings` (`saveMom`) di
 * prototype v18. Attendee (many-to-many ke `users`) & action item
 * dipisah ke tabel sendiri (`meeting_attendees`, `meeting_action_items`)
 * di migration berikutnya — bukan JSON column, biar action item bisa
 * ditelusuri balik dari `work_items` (lihat catatan di migration
 * `create_work_items_table`).
 *
 * `blasted_at` (timestamp, bukan boolean) — padanan tombol "Blast
 * Summary" prototype yang nge-push ringkasan MoM jadi Memo ke attendee.
 * Dipilih timestamp (bukan boolean doang) biar kelihatan juga KAPAN
 * di-blast, berguna kalau nanti ada yang nanya "udah di-share belum ke
 * tim / kapan".
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('agenda');
            $table->string('persons_text')->nullable();
            $table->text('notes')->nullable();
            $table->text('decisions')->nullable();
            $table->timestamp('blasted_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};