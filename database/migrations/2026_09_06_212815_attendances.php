<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * add_multi_session_to_attendances
 * ---------------------------------------------------------------------
 * Fase 7 — dua perubahan struktural ke `attendances`:
 *
 * 1. Multi-sesi buat mode Lapangan/Gigs (kesepakatan Fase 4 dulu:
 *    1 baris per user per hari, `unique(user_id,date)`). Sekarang
 *    ditambah `session_number` (mulai dari 1), unique constraint-nya
 *    diganti jadi `unique(user_id,date,session_number)`. Mode
 *    Kantor/WFH TETAP cuma 1 sesi per hari (dipaksa di controller,
 *    bukan di DB — soalnya constraint DB gak bisa syarat "cuma kalau
 *    mode X"), Lapangan/Gigs boleh berkali-kali (check-in lagi bikin
 *    `session_number` baru).
 *
 * 2. Kolom `mode` diganti dari `enum('kantor','wfh')` jadi
 *    `string(20)` supaya bisa nambah 'lapangan'/'gigs'. Validasi nilai
 *    yang boleh murni di `ClockInRequest::rules()`
 *    (`in:kantor,wfh,lapangan,gigs`), bukan di level DB.
 *
 * 3. `auto_closed` — nandain baris yang clock-out-nya dipaksa sistem
 *    (reconcile jam 20:00 kelewat, lupa checkout, gak ada lembur
 *    disetujui) — beda dari checkout manual biasa, biar kelihatan di
 *    riwayat & rekap kalau itu bukan jam pulang beneran dari user.
 *
 * ⚠️ **Dependency baru**: ganti tipe kolom enum→string butuh
 * `doctrine/dbal` (`composer require doctrine/dbal`) — Laravel butuh
 * itu buat baca skema kolom existing sebelum diubah. Ini pure-PHP,
 * gak butuh compile/extension khusus, jadi tetap aman buat alur
 * `composer install` lokal lalu upload `vendor/` ke cPanel (gak perlu
 * jalanin composer di server). Ini migration PERTAMA di project yang
 * butuh dependency ini — kalau `composer require doctrine/dbal` belum
 * dijalanin, migration ini bakal error "Class Doctrine\DBAL... not
 * found" pas `php artisan migrate`.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        // PENTING: urutan di bawah ini sengaja bikin unique index baru
        // (user_id,date,session_number) DULU sebelum drop unique lama
        // (user_id,date). Keduanya sama-sama diawali kolom `user_id`,
        // yang juga punya foreign key ke `users`. MySQL/InnoDB "numpang"
        // index unique paling kiri yang cocok itu sebagai index
        // pendukung FK `user_id` — kalau index lama didrop duluan
        // (sebelum index penggantinya ada), FK jadi kehilangan index
        // pendukung sesaat dan MySQL nolak dengan error 1553
        // "needed in a foreign key constraint". Bikin index baru dulu
        // baru drop yang lama menghindari itu.
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('mode', 20)->default('kantor')->change();
            $table->unsignedTinyInteger('session_number')->default(1)->after('date');
            $table->boolean('auto_closed')->default(false)->after('clock_out_photo');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'date', 'session_number']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        // Sama seperti up(): bikin index pengganti (user_id,date) dulu
        // sebelum drop unique (user_id,date,session_number), biar FK
        // `user_id` selalu punya index pendukung.
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['user_id', 'date']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'session_number']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['session_number', 'auto_closed']);
            $table->enum('mode', ['kantor', 'wfh'])->default('kantor')->change();
        });
    }
};