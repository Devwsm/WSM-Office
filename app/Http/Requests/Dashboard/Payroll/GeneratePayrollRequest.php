<?php

namespace App\Http\Requests\Dashboard\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/**
 * GeneratePayrollRequest
 * ---------------------------------------------------------------------
 * Fase 12 — validasi form "Generate Payroll" (periode + opsional pilih
 * karyawan tertentu, default semua karyawan yang punya `salary_base`
 * terisi). Dipakai `PayrollController::generate()`.
 * ---------------------------------------------------------------------
 */
class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:payroll,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['required', 'date_format:Y-m'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}