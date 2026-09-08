<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateEmployeeRequest
 * ---------------------------------------------------------------------
 * Validasi form edit karyawan/manajer/HRD oleh Owner (Fase 2). Sama
 * persis field-nya dengan StoreEmployeeRequest, cuma `password` jadi
 * opsional (kosongin field itu di form kalau gak mau ganti password)
 * dan `email` unique-nya ngecualiin baris user itu sendiri.
 *
 * (Dipulihkan 2026-09-08 — file ini sebelumnya kepakai buat nyimpen
 * class `UpdateOfficeSettingRequest` secara gak sengaja, ketuker pas
 * development Fase 7, jadi `UpdateEmployeeRequest` yang asli sempat
 * hilang. Dua-duanya sekarang sudah dipisah ke file masing-masing.)
 * ---------------------------------------------------------------------
 */
class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($this->route('employee'))],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['owner', 'manajer', 'karyawan', 'hrd'])],
            'manager_id' => ['nullable', 'exists:users,id'],
            'division' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'annual_leave_entitlement' => ['nullable', 'integer', 'min:0', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'manager_id.exists' => 'Atasan yang dipilih tidak valid.',
        ];
    }
}