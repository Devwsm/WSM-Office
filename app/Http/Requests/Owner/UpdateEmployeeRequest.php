<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * UpdateEmployeeRequest
 * ---------------------------------------------------------------------
 * Validasi form edit karyawan (Fase 2). Password dikosongkan = tidak
 * diganti. `manager_id` dicegah nunjuk ke diri sendiri lewat
 * withValidator() di bawah — rule bawaan Laravel tidak punya cara
 * langsung buat "field != route parameter saat ini".
 *
 * (Dipulihkan 2026-09-08 dari git history commit da33da5 — file ini
 * sebelumnya kepakai buat nyimpen class `UpdateOfficeSettingRequest`
 * secara gak sengaja, ketuker pas development Fase 7, jadi
 * `UpdateEmployeeRequest` yang asli sempat hilang termasuk validasi
 * self-manager di bawah. Dua-duanya sekarang sudah dipisah ke file
 * masing-masing.)
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
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['owner', 'manajer', 'karyawan', 'hrd'])],
            'manager_id' => ['nullable', 'exists:users,id'],
            'division' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'annual_leave_entitlement' => ['nullable', 'integer', 'min:0', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            // Fase 12 — field payroll, ditunda dari Fase 7 (lihat README).
            'salary_base' => ['nullable', 'numeric', 'min:0'],
            'target_hours_per_day' => ['nullable', 'integer', 'min:1', 'max:24'],
            'flat_overtime_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'manager_id.exists' => 'Atasan yang dipilih tidak valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->route('employee');

            if ($employee && (int) $this->input('manager_id') === $employee->id) {
                $validator->errors()->add('manager_id', 'Karyawan tidak bisa jadi atasannya sendiri.');
            }
        });
    }
}