<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * create_project_sections_table
 * ---------------------------------------------------------------------
 * 2026-09-30 — Kelola section di dalam project (padanan
 * `projectSectionOrderV24` + `sectionColors` + `deleteProjectSectionV26`
 * di prototype v32): warna, urutan, tambah, hapus.
 *
 * Sebelumnya section HANYA teks di `work_items.section` — warna dihitung
 * dari nama (crc32), urutan = item pertama yang dibuat. Tabel ini cuma
 * menyimpan pengaturan tampilan per (project, nama section). Kolom
 * `work_items.section` TETAP string apa adanya, jadi import/export, MoM,
 * reminder, dan kalender tidak perlu berubah.
 *
 * `color` NULL = pakai warna default dari nama (ProjectSection::
 * defaultColorFor) — persis warna yang sudah dilihat tim hari ini.
 *
 * Backfill di up(): setiap section yang sudah dipakai item diberi baris
 * dengan urutan = urutan item pertamanya (MIN(work_items.id)), sama
 * dengan urutan tampilan sebelum migration ini. Tidak ada yang bergeser.
 * Item tanpa project / tanpa section tidak masuk (tidak bisa diatur).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        $this->backfillFromWorkItems();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sections');
    }

    private function backfillFromWorkItems(): void
    {
        $pairs = DB::table('work_items')
            ->whereNotNull('project_id')
            ->where('section', '!=', '')
            ->selectRaw('project_id, section, MIN(id) as first_id')
            ->groupBy('project_id', 'section')
            ->orderBy('project_id')
            ->orderBy('first_id')
            ->get();

        $order = [];
        $now = now();
        $rows = [];

        foreach ($pairs as $pair) {
            $order[$pair->project_id] = ($order[$pair->project_id] ?? 0) + 1;
            $rows[] = [
                'project_id' => $pair->project_id,
                'name' => mb_substr($pair->section, 0, 80),
                'color' => null,
                'sort_order' => $order[$pair->project_id],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // insertOrIgnore: kalau ada 2 nama yang cuma beda huruf besar/kecil
        // (collation MySQL case-insensitive), yang kedua dilewati — ia tetap
        // tampil sebagai section biasa tanpa kontrol (lihat ProjectSection).
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('project_sections')->insertOrIgnore($chunk);
        }
    }
};