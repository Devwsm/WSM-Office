<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_personalization_groups_and_landing
 * ---------------------------------------------------------------------
 * 2026-10-02 — selisih prototype #10. Aman dijalankan di production:
 * semua kolom baru nullable dan tabel baru kosong, jadi tampilan dan
 * perilaku yang sekarang berjalan tidak berubah sampai ada yang
 * mengisinya.
 *
 * - users.avatar_path   : foto profil (disk private, lewat PrivateFile).
 * - users.theme_colors  : warna tampilan pribadi (JSON), NULL = bawaan.
 * - team_groups + pivot : kelompok tim custom (padanan teamGroupsV24)
 *                         yang bisa dipilih sebagai visibility project.
 * - office_settings.landing_content : tagline + banner halaman depan
 *                         publik (JSON), NULL = teks bawaan di model.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable();
            $table->json('theme_colors')->nullable();
        });

        Schema::create('team_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('color', 7)->default('#DCE8FF');
            $table->timestamps();
        });

        Schema::create('team_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_group_id')->constrained('team_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['team_group_id', 'user_id']);
        });

        Schema::table('office_settings', function (Blueprint $table) {
            $table->json('landing_content')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('office_settings', function (Blueprint $table) {
            $table->dropColumn('landing_content');
        });

        Schema::dropIfExists('team_group_user');
        Schema::dropIfExists('team_groups');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'theme_colors']);
        });
    }
};