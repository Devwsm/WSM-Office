<?php

namespace App\Http\Requests\Dashboard\Contracts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ContractRequest
 * ---------------------------------------------------------------------
 * Fase 11 — dipakai buat store & update EmployeeContract. `file`
 * SENGAJA cuma wajib pas STORE (POST) — pas UPDATE (PATCH) boleh gak
 * upload ulang, berarti file lama dipertahankan, controller yang
 * nentuin (lihat ContractController::update()).
 * ---------------------------------------------------------------------
 */
class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:contracts,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'file' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}