<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_employee_contracts_table
 * ---------------------------------------------------------------------
 * Fase 11 — padanan `state.contracts` (`saveContract`) di prototype
 * v18. Nempel modul `contracts` (`DashboardAccess::MODULES['contracts']`,
 * label UI "Contract Monitoring") yang slotnya udah ada dari Fase 6a.
 *
 * Nama tabel SENGAJA `employee_contracts`, bukan `contracts` polos —
 * biar gak ketuker sama `legal_documents` (Fase 14) yang di prototype
 * juga kadang disebut "contracts" tapi beda konteks (Album Contracts /
 * Royalty Agreements, bukan kontrak kerja karyawan).
 *
 * File disimpan sebagai path di storage (`file_path`, lewat
 * `asset('storage/...')`) — sama pola kayak `attendances.clock_in_photo`
 * — BUKAN blob di DB kayak prototype (`putContractBlob` ke IndexedDB,
 * itu batasan browser-only, gak relevan buat server beneran).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};