<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_work_items_table
 * ---------------------------------------------------------------------
 * Fase 9 — padanan `state.tasks` ("Item / Task") di prototype v18.
 * SENGAJA dinamai `work_items`, bukan `tasks` polos — biar gak ketuker
 * konsep sama `employee_contracts`/`legal_documents` yang di prototype
 * juga sering disebut generik, dan biar jelas ini nempel di modul
 * `work` (`DashboardAccess::MODULES['work']`) dari Fase 6a.
 *
 * `section` SENGAJA `string` biasa, BUKAN enum — prototype punya daftar
 * saran (`TRACKER_SECTIONS`) tapi juga nulis section custom di runtime
 * (`Reminder / Admin`, `Meeting / MoM` dari MoM action item). Validasi
 * "section yang wajar" cukup di form (select + opsi custom), bukan
 * dipaksa di level DB.
 *
 * `focus` juga `string` nullable, bukan hasil hitung yang disimpan —
 * prototype nge-compute "Auto" (HARI INI/BESOK/KELEWAT/dst) dari
 * `due_date` tiap render. Kalau kolom ini NULL, controller/accessor
 * yang hitung on-the-fly (sama prinsip kayak status Attendance yang
 * gak disimpan biar gak basi).
 *
 * `meeting_action_item_id` ditambah lewat migration terpisah SETELAH
 * tabel `meeting_action_items` ada (lihat
 * `add_meeting_action_item_to_work_items_table`), biar urutan migration
 * gak muter (work_items dibuat sebelum meetings/meeting_action_items).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('section');
            $table->string('item_no')->nullable();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('focus')->nullable();
            $table->foreignId('pic_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('additional_pic')->nullable();
            $table->enum('progress', [
                'Pending',
                'On Development',
                'Follow Up',
                'Confirmed',
                'Done',
                'Postpone',
            ])->default('Pending');
            $table->enum('priority', ['Low', 'Medium', 'High'])->nullable();
            $table->text('notes')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_reminder')->default(false);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
