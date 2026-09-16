<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * memo_audience_and_active
 * ---------------------------------------------------------------------
 * 2026-09-16 — Memo Forum, temuan audit prototype: 3 hal yang belum ada
 * di `memos` sebelumnya —
 *   1. `audience` — dulu SEMUA memo otomatis ke semua orang, gak ada
 *      cara ngirim cuma ke sebagian karyawan (prototype: dropdown
 *      "Penerima" — Semua Karyawan / grup tertentu). Di sini disederhanain
 *      jadi 'semua' vs 'tertentu' (pilih individu lewat `memo_recipients`)
 *      — belum ada konsep "grup custom" (WS Team/Operating Team dst di
 *      prototype) karena entity Group belum ada sama sekali di
 *      WSM-Office, itu di luar scope eksekusi ini.
 *   2. `active` — dulu gak ada cara "matiin" memo lama biar gak nongol
 *      lagi di kartu Home tanpa harus dihapus permanen (prototype:
 *      tombol "Deactivate"). Memo nonaktif TETAP ada di listing
 *      manajemen (dashboard/work), cuma disembunyikan dari
 *      `HomeController`/kartu Home App Mode.
 *   3. Read/Hidden count — datanya sebenernya UDAH ada dari Fase 8
 *      (tabel `memo_reads`), cuma nggak pernah ditampilin di halaman
 *      manajemen. Nggak butuh kolom baru, cuma query di
 *      Memo::readCount()/hiddenCount().
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->enum('audience', ['semua', 'tertentu'])->default('semua')->after('pinned');
            $table->boolean('active')->default(true)->after('audience');
        });

        Schema::create('memo_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['memo_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_recipients');

        Schema::table('memos', function (Blueprint $table) {
            $table->dropColumn(['audience', 'active']);
        });
    }
};