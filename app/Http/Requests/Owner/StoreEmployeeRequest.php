<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * StoreEmployeeRequest
 * ---------------------------------------------------------------------
 * Validasi form tambah karyawan/manajer/HRD baru oleh Owner (Fase 2).
 * Password wajib diisi karena user baru butuh cara buat login pertama
 * kali — belum ada flow invite/reset link (nanti bisa ditambah kalau
 * mau lebih rapi).
 * ---------------------------------------------------------------------
 */
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware role:owner sudah jaga route-nya; request ini cuma
        // dipanggil dari controller yang sudah di belakang middleware itu.
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