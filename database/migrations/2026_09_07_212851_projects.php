<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_projects_table
 * ---------------------------------------------------------------------
 * Fase 9 (Work Control Lanjutan) — project master, padanan `state.projects`
 * di prototype v18 (`saveProjectV10`). `work_items` (task/item tracker),
 * `meetings` (MoM), dan `project_budgets` (Fase 13) semua nunjuk balik
 * ke sini lewat `project_id` nullable — nullable karena prototype juga
 * ngasih opsi "General WSM / Cross Project" (item/meeting yang gak
 * spesifik ke 1 project).
 *
 * `status` persis `TRACKER_PROJECT_STATUSES` di prototype — SENGAJA
 * bukan status yang sama kayak `work_items.progress` (beda konsep:
 * status project vs progress per item).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium');
            $table->enum('status', [
                'On Development',
                'Follow Up',
                'Done',
                'Postpone',
                'Pending',
                'Confirmed',
            ])->default('On Development');
            $table->foreignId('lead_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tracker_url')->nullable();
            $table->text('progress_recap')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};