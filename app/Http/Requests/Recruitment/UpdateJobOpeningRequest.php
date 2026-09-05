<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateJobOpeningRequest
 * ---------------------------------------------------------------------
 * Validasi form edit lowongan (Fase 3). `slug` tidak pernah diubah lagi
 * setelah lowongan dibuat, supaya link publik yang sudah dibagikan
 * (mis. di medsos) tidak mendadak rusak — lihat
 * JobOpeningController@update.
 * ---------------------------------------------------------------------
 */
class UpdateJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'internship'])],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'published', 'closed'])],
        ];
    }
}