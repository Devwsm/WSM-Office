<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * UpdatePasswordRequest
 * ---------------------------------------------------------------------
 * Validasi form ganti password di tab Profile (bottom-nav app-mobile).
 * `current_password` dicek manual lewat withValidator() (bukan rule
 * `current_password` bawaan Laravel) karena guard default project ini
 * cuma 'web' dan field-nya harus dipetakan ke pesan bahasa Indonesia
 * yang konsisten sama form lain (lihat StoreOvertimeRequestRequest).
 * ---------------------------------------------------------------------
 */
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('current_password')) {
                return;
            }

            if (! Hash::check($this->input('current_password'), Auth::user()->password)) {
                $validator->errors()->add('current_password', 'Password saat ini salah.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ];
    }
}