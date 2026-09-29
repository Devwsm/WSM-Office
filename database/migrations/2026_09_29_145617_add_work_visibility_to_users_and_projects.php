<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_work_visibility_to_users_and_projects
 * ---------------------------------------------------------------------
 * 2026-09-29 — padanan "Work Team" (karyawan) dan "Visibility" (project)
 * di prototype v22. Aman dijalankan di production:
 * - `projects.visibility` default 'all' -> semua project lama tetap
 *   terlihat semua karyawan sampai Owner sendiri membatasinya.
 * - `users.work_team` NULL -> tim ditebak dari divisi/jabatan
 *   (User::workTeam()), jadi tidak ada data yang perlu diisi dulu.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('work_team', 20)->nullable();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('visibility', 20)->default('all');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('work_team');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};