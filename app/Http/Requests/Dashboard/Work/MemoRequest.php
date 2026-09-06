<?php

namespace App\Http\Requests\Dashboard\Work;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * MemoRequest
 * ---------------------------------------------------------------------
 * Fase 6b — dipakai buat store & update Memo/MoM. meeting_date &
 * attendees cuma relevan buat type=mom, tapi sengaja tetap 'nullable'
 * (bukan 'required_if') — MoM tanpa tanggal/daftar hadir masih valid,
 * cuma kurang lengkap, bukan error.
 * ---------------------------------------------------------------------
 */
class MemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:work,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['memo', 'mom'])],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string'],
            'meeting_date' => ['nullable', 'date'],
            'attendees' => ['nullable', 'string', 'max:255'],
            'pinned' => ['sometimes', 'boolean'],
        ];
    }
}