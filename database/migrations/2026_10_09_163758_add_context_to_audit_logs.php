<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_context_to_audit_logs
 * ---------------------------------------------------------------------
 * 2026-10-09 — Audit Log dilengkapi supaya masalah mudah ditelusuri:
 *
 *   ip_address — alamat IP request yang memicu catatan (null untuk aksi sistem)
 *   area       — nama halaman/area aplikasi ("Work Tracker", "Payroll Overview", ...),
 *                diambil dari nama route lewat config/presence.php; dipakai filter
 *
 * Hanya menambah kolom nullable — catatan lama tetap utuh (kedua kolom kosong).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('detail');
            $table->string('area', 120)->nullable()->after('ip_address')->index();
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropColumn(['ip_address', 'area']);
        });
    }
};