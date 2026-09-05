<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_job_openings_table
 * ---------------------------------------------------------------------
 * Fase 3 — lowongan yang dikelola HRD/Owner, muncul di halaman Karir
 * publik kalau status = 'published'. Alur status: draft (belum tayang,
 * masih disiapkan) -> published (tayang, nerima lamaran) -> closed
 * (ditutup, riwayat & pelamar lama tetap ada). Tidak ada hard delete
 * untuk sekarang — cukup ditutup (closed) kalau sudah tidak dibuka.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('division')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'internship'])
                ->default('full_time');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_openings');
    }
};