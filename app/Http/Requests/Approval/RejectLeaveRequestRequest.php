<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

/**
 * RejectLeaveRequestRequest
 * ---------------------------------------------------------------------
 * Fase 5 — alasan penolakan wajib diisi (kesepakatan awal Fase 5: kalau
 * ditolak, karyawan boleh ajukan ulang karena bakal tau alasannya).
 * ---------------------------------------------------------------------
 */
class RejectLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision_note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision_note.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}