<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_contact_messages_table
 * ---------------------------------------------------------------------
 * Fase 1 (susulan, 2026-09-13) — nutup TODO lama di
 * `PageController::storeContact()`. Sebelumnya form Kontak publik cuma
 * render & validasi, pesan gak pernah tersimpan ke mana pun.
 *
 * Keputusan (README §2.2): pesan disimpan ke tabel ini, ditinjau lewat
 * halaman dashboard tersendiri (`Owner\ContactMessageController`) —
 * SENGAJA belum jadi modul `dashboard_access` (belum bisa didelegasikan
 * ke staf lain), cuma Owner dulu yang bisa lihat, sampai ada keputusan
 * lanjutan mau didelegasikan ke modul apa.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->enum('status', ['baru', 'dibaca'])->default('baru');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};