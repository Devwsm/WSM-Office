<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * create_project_budgets_table
 * ---------------------------------------------------------------------
 * Fase 13 — padanan `state.projectBudgets` (`saveBudgetEntry`) di
 * prototype v18. Nempel modul `budget` (`DashboardAccess::MODULES
 * ['budget']`, label UI "Project Budgeting") yang slotnya udah ada
 * dari Fase 6a.
 *
 * `category` SENGAJA `string` bebas (Creative/Marketing/Production/dll)
 * — di prototype ini input teks polos, bukan dropdown/enum.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('category');
            $table->string('item');
            $table->decimal('budget', 14, 2)->default(0);
            $table->decimal('actual', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budgets');
    }
};