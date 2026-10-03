<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * project_section_viewers
 * ---------------------------------------------------------------------
 * 2026-10-03 — visibility PER ORANG di level section Work Tracker.
 *
 * Aturan: section TANPA baris di tabel ini = terbuka untuk semua yang
 * boleh melihat project-nya (perilaku sekarang, tidak ada yang berubah).
 * Section DENGAN minimal 1 baris = hanya terlihat oleh orang-orang itu,
 * Owner/Developer, dan PIC item di section tersebut (task milik sendiri
 * tidak pernah disembunyikan, sama seperti pembatasan project).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_section_viewers', function (Blueprint $table) {
            $table->foreignId('project_section_id')->constrained('project_sections')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['project_section_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_section_viewers');
    }
};