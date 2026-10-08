<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_presence_to_users
 * ---------------------------------------------------------------------
 * 2026-10-08 — Monitor Login (Dashboard > IT). Menyimpan jejak "online
 * atau tidak" per karyawan di tabel users supaya halaman monitor cukup
 * satu query (tanpa join ke tabel sessions).
 *
 *   last_login_at    — login terakhir (event Login)
 *   last_logout_at   — logout terakhir (event Logout); dipakai supaya
 *                      user yang sudah logout langsung terbaca Offline
 *   last_seen_at     — aktivitas terakhir (buka halaman / heartbeat)
 *   last_seen_route  — nama route halaman terakhir (bukan URL/query)
 *   last_seen_label  — label halaman yang ramah dibaca ("Work Tracker")
 *
 * Hanya menambah kolom nullable — tidak mengubah/menghapus data lama.
 * Karyawan yang belum login lagi setelah rilis ini tampil "Belum ada
 * data" sampai mereka membuka aplikasi.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_logout_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->string('last_seen_route', 120)->nullable();
            $table->string('last_seen_label', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn(['last_login_at', 'last_logout_at', 'last_seen_at', 'last_seen_route', 'last_seen_label']);
        });
    }
};