<?php

namespace App\Http\Requests\Dashboard\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BudgetRequest
 * ---------------------------------------------------------------------
 * Fase 13 — dipakai buat store & update ProjectBudget. `category`
 * SENGAJA input teks bebas (bukan dropdown/enum) — persis prototype
 * v18 (lihat catatan migration `create_project_budgets_table`).
 * ---------------------------------------------------------------------
 */
class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:budget,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'category' => ['required', 'string', 'max:100'],
            'item' => ['required', 'string', 'max:150'],
            'budget' => ['required', 'numeric', 'min:0'],
            'actual' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}