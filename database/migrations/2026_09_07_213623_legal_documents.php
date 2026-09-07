<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_legal_documents_table
 * ---------------------------------------------------------------------
 * Fase 14 (BARU, gak ada di rencana lama) — padanan `state.legalDocs`
 * (`saveLegalDocV18`) di prototype v18. Prototype punya 2 halaman
 * terpisah (Album Contracts / Royalty Agreements) tapi cuma 1 level
 * akses & 1 struktur data — disatuin di sini jadi 1 tabel + kolom
 * `category` pembeda, sama pola kayak `memos.type` ('memo' vs 'mom').
 *
 * SENGAJA dipisah dari `employee_contracts` (Fase 11) — beda konteks:
 * ini kontrak Album/Royalti (pihak eksternal: label, artist,
 * publisher), bukan kontrak kerja karyawan.
 *
 * Modul access `legal` ditambah ke `DashboardAccess::MODULES` di
 * migration terpisah (lihat catatan di model `LegalDocument`), dipakai
 * bareng buat kedua kategori (`album` & `royalty`) — prototype juga
 * cuma 1 level akses buat 2 halaman itu, gak dipecah
 * `legal_album`/`legal_royalty`.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['album', 'royalty']);
            $table->string('title');
            $table->string('party')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_documents');
    }
};