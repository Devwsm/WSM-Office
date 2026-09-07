<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_meeting_attendees_table
 * ---------------------------------------------------------------------
 * Fase 9 — pivot murni (bukan pakai `id()` sendiri), padanan
 * checkbox attendee (`attendeesMarkup()`) di form MoM prototype.
 * Primary key komposit `(meeting_id, user_id)` — 1 user cuma bisa
 * jadi attendee 1x per meeting.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['meeting_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attendees');
    }
};