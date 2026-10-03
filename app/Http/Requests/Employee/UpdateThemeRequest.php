<?php

namespace App\Http\Requests\Employee;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Warna tampilan pribadi (Profile → Personal Colors). Semua field wajib
 * berupa hex 6 digit; nilai lain ditolak supaya tidak ada string bebas
 * yang masuk ke atribut style di layout.
 */
class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        foreach (array_keys(User::THEME_DEFAULTS) as $key) {
            $rules[$key] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'background' => 'Page Background',
            'text' => 'Main Text',
            'primary' => 'Primary / Active',
            'success' => 'Success Badge',
            'attention' => 'Attention Badge',
            'danger' => 'Overdue / Danger',
            'leave' => 'Paid Leave Banner',
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => ':attribute harus berupa warna hex 6 digit, contoh #f2efe7.',
            '*.required' => ':attribute wajib diisi.',
        ];
    }
}