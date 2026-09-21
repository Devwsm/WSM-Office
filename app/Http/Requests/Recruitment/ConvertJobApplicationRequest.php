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
            'role' => ['required', Rule::in($this->assignableRoles())],
            'manager_id' => ['nullable', 'exists:users,id'],
            'division' => ['nullable', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'join_date' => ['nullable', 'date'],
            'annual_leave_entitlement' => ['nullable', 'integer', 'min:0', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    /**
     * Role yang boleh dipilih pembuat akun. Akun Owner punya akses penuh ke
     * semua modul, jadi HANYA Owner yang boleh membuat Owner baru; HRD (atau
     * siapa pun dengan akses recruitment `manage`) tidak boleh menaikkan
     * pelamar — atau dirinya sendiri lewat akun kedua — menjadi Owner.
     *
     * @return array<int, string>
     */
    private function assignableRoles(): array
    {
        $roles = ['manajer', 'karyawan', 'hrd'];

        if ($this->user()?->isOwner()) {
            $roles[] = 'owner';
        }

        return $roles;
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role itu tidak boleh dipilih. Akun Owner hanya bisa dibuat oleh Owner.',
            'email.unique' => 'Email ini sudah dipakai user lain.',
            'manager_id.exists' => 'Atasan yang dipilih tidak valid.',
        ];
    }
}