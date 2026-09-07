<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * add_meeting_action_item_to_work_items_table
 * ---------------------------------------------------------------------
 * Fase 9 — nyambungin work_items balik ke meeting_action_items, dipisah
 * jadi migration sendiri (bukan langsung di `create_work_items_table`)
 * karena `meeting_action_items` baru ada belakangan (work_items dibuat
 * duluan biar `meetings`/`meeting_action_items` bisa nunjuk ke
 * `projects` dulu tanpa muter).
 *
 * Ini TAMBAHAN di luar prototype: prototype cuma nulis teks
 * "From MoM: {agenda}" di notes task hasil dari action item, gak ada
 * relasi beneran. Di sini kita simpan relasi FK asli biar bisa
 * ditelusuri balik / di-query, teks "From MoM: ..." di notes tetap
 * boleh dipertahankan di controller sebagai info tambahan yang enak
 * dibaca langsung tanpa join.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->foreignId('meeting_action_item_id')
                ->nullable()
                ->after('project_id')
                ->constrained('meeting_action_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meeting_action_item_id');
        });
    }
};