<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_system_changelogs_table
 * ---------------------------------------------------------------------
 * Fase 15 (BARU) — padanan `state.systemChangelogCustom`
 * (`saveCustomChangeLog`) di prototype v18: catatan rilis fitur buat
 * end-user Owner lewat UI, beda dari README repo ini yang buat tim dev.
 *
 * `modules` & `changes` disimpan sebagai JSON array (cast `array` di
 * model) — di form-nya tetap input teks biasa (modules dipisah koma,
 * changes 1 baris = 1 bullet di textarea), tinggal displit jadi array
 * pas disimpan controller, sama persis behaviour prototype
 * (`$('changeModules').value` dipisah koma, `$('changeChanges').value`
 * dipisah baris baru).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_changelogs', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->date('release_date');
            $table->enum('status', ['Planned', 'Released'])->default('Planned');
            $table->json('modules')->nullable();
            $table->string('title');
            $table->json('changes');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_changelogs');
    }
};