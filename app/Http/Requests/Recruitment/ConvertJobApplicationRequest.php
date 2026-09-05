<?php

namespace App\Http\Requests\Recruitment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ConvertJobApplicationRequest
 * ---------------------------------------------------------------------
 * Validasi form "Terima & Buatkan Akun" — convert pelamar (job_applications)
 * jadi akun karyawan beneran (users), dipakai HRD/Owner dari halaman
 * detail pelamar (Fase 3). Field-nya sengaja disamakan dengan
 * Owner\StoreEmployeeRequest karena hasil akhirnya sama-sama bikin baris
 * `users` baru — bedanya nama/email di sini sudah keisi duluan dari data
 * lamaran (lihat JobApplicationController@convert).
 * ---------------------------------------------------------------------
 */
class ConvertJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
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