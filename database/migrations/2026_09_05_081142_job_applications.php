<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_job_applications_table
 * ---------------------------------------------------------------------
 * Fase 3 — lamaran dari form publik di halaman detail lowongan. Masih
 * teks saja (nama, email, telepon, pesan) — upload CV menyusul nanti,
 * jadi TIDAK ada kolom file di sini dulu supaya nggak perlu migration
 * ubah struktur pas fitur upload ditambah (tinggal migration baru).
 *
 * Pipeline status (dipakai HRD/Owner di dashboard pelamar):
 *   baru -> ditinjau -> interview -> ditawari -> diterima / ditolak
 *
 * converted_user_id kesisi begitu pelamar di-convert jadi akun karyawan
 * (lihat Recruitment\JobApplicationController@convert).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['baru', 'ditinjau', 'interview', 'ditawari', 'diterima', 'ditolak'])
                ->default('baru');
            $table->text('notes')->nullable();
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};