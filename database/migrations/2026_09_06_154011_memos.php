<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_memos_table
 * ---------------------------------------------------------------------
 * Fase 6b — modul pertama yang dibangun di atas fondasi permission
 * Fase 6a, nempel di modul 'work' (Work Control). 2 jenis catatan dalam
 * 1 tabel, dibedain kolom `type`:
 *   - 'memo' — pengumuman internal biasa dari Owner/Manajer ke tim.
 *   - 'mom'  — Minutes of Meeting, punya tanggal rapat & daftar hadir.
 *
 * `pinned` dipakai buat nahan memo penting di atas terus di kartu
 * "Info dari Owner" (Home) walau ada memo baru — bukan diurutin murni
 * dari created_at.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['memo', 'mom'])->default('memo');
            $table->string('title');
            $table->text('content');
            $table->date('meeting_date')->nullable();
            $table->string('attendees')->nullable();
            $table->boolean('pinned')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};