<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * budget_categories_viewers_and_project_plans
 * ---------------------------------------------------------------------
 * 2026-10-04 — Project Budgeting disamakan konsepnya dengan Work Tracker:
 * Project > Kategori (padanan "section") > Item.
 *
 *  - budget_categories        : warna & urutan kategori per project. Nama
 *                               kategori di `project_budgets.category` TETAP
 *                               string apa adanya (sama pola project_sections),
 *                               jadi import/export/PDF tidak perlu berubah.
 *  - budget_category_viewers  : akses PER ORANG di level kategori. Kategori
 *                               tanpa baris = terbuka untuk semua yang punya
 *                               akses modul Budgeting; dengan baris = hanya
 *                               orang itu + Owner/Developer.
 *  - project_budget_plans     : "Project Budget" = anggaran awal per project
 *                               (1 baris per project, hanya diubah lewat form
 *                               edit). Beda dari "Budget Allocation" yang
 *                               dihitung dari jumlah item.
 *
 * Aditif: tidak mengubah/menghapus baris `project_budgets` yang sudah ada
 * di production. Backfill kategori mengikuti urutan item pertama (MIN(id)),
 * pengelompokan tanpa membedakan huruf besar/kecil & spasi pinggir (sama
 * dengan BudgetReport).
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'name']);
        });

        Schema::create('budget_category_viewers', function (Blueprint $table) {
            $table->foreignId('budget_category_id')->constrained('budget_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['budget_category_id', 'user_id']);
        });

        Schema::create('project_budget_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained('projects')->cascadeOnDelete();
            $table->decimal('project_budget', 14, 2)->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->backfillCategories();
    }

    public function down(): void
    {
        Schema::dropIfExists('project_budget_plans');
        Schema::dropIfExists('budget_category_viewers');
        Schema::dropIfExists('budget_categories');
    }

    private function backfillCategories(): void
    {
        $seen = [];
        $order = [];
        $rows = [];

        $lines = DB::table('project_budgets')->orderBy('id')->get(['project_id', 'category']);

        foreach ($lines as $line) {
            $name = mb_substr(trim((string) $line->category), 0, 100);

            if ($name === '') {
                continue;
            }

            $key = $line->project_id . '|' . mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $order[$line->project_id] = ($order[$line->project_id] ?? 0) + 1;
            $rows[] = [
                'project_id' => $line->project_id,
                'name' => $name,
                'color' => null,
                'sort_order' => $order[$line->project_id],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('budget_categories')->insertOrIgnore($chunk);
        }
    }
};