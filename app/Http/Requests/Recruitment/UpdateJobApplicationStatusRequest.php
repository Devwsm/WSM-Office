<?php

namespace App\Http\Requests\Recruitment;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateJobApplicationStatusRequest
 * ---------------------------------------------------------------------
 * Validasi ubah status pipeline pelamar + catatan internal HRD/Owner
 * (Fase 3). Lihat App\Models\JobApplication::STATUSES untuk urutan
 * tahapnya.
 * ---------------------------------------------------------------------
 */
class UpdateJobApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(JobApplication::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}