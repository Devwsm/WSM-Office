<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_meeting_action_items_table
 * ---------------------------------------------------------------------
 * Fase 9 — action item per MoM (`momActionRow()` di prototype: task,
 * PIC, due date). `pic_all` (boolean) padanan opsi PIC "ALL TEAM"
 * (`pic:'all'`) di prototype — dipisah dari `pic_employee_id` biar FK-nya
 * tetap bersih (gak ada nilai sentinel kayak string 'all' nyempil di
 * kolom FK).
 *
 * Kalau checkbox "Masukkan action items otomatis ke Work Tracker"
 * dicentang (default ON di prototype), controller Fase 9 bikin baris
 * `work_items` baru per action item DAN isi
 * `work_items.meeting_action_item_id` balik ke sini — lihat migration
 * `add_meeting_action_item_to_work_items_table`.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('task');
            $table->foreignId('pic_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('pic_all')->default(false);
            $table->date('due_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_action_items');
    }
};