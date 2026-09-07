<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * UnlockDashboardRequest
 * ---------------------------------------------------------------------
 * Validasi layar unlock "Lock Dashboard". Password yang dicek = password
 * akun user yang sedang login sendiri (bukan 1 password manajemen
 * bersama kayak prototype) — lihat README untuk alasan lengkap. Pola
 * pengecekan manual (`withValidator`) disamain sama
 * `Employee\UpdatePasswordRequest`.
 * ---------------------------------------------------------------------
 */
class UnlockDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('password')) {
                return;
            }

            if (! Hash::check($this->input('password'), Auth::user()->password)) {
                $validator->errors()->add('password', 'Password salah.');
            }
        });
    }
}