<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreJobOpeningRequest
 * ---------------------------------------------------------------------
 * Validasi form tambah lowongan oleh HRD/Owner (Fase 3). `slug` sengaja
 * TIDAK divalidasi di sini karena dibuat otomatis dari `title` di
 * controller (lihat JobOpeningController@store), bukan input manual.
 * ---------------------------------------------------------------------
 */
class StoreJobOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware role:hrd,owner sudah jaga route-nya.
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