<?php

namespace App\Http\Requests\Dashboard\Kpi;

use App\Models\Kpi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * KpiRequest
 * ---------------------------------------------------------------------
 * Fase 10 — dipakai buat store & update Kpi dari sisi Owner/Manajer
 * (kelola KPI seluruh tim). `current` SENGAJA required (bukan
 * nullable/default 0 doang) — biar Owner sadar isi progress terkini
 * pas nambah KPI baru, bukan ke-skip diam-diam terus kelihatan 0% di
 * kartu "My KPI" karyawan.
 * ---------------------------------------------------------------------
 */
class KpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:kpi,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:150'],
            'period' => ['required', 'string', 'max:50'],
            'target' => ['required', 'numeric', 'min:0'],
            'current' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(Kpi::STATUSES)],
            'owner_note' => ['nullable', 'string'],
        ];
    }
}