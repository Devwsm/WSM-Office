<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Editor beranda publik (tagline + 4 banner). Akses sudah dijaga middleware
 * grup route Owner/Developer, jadi di sini cukup validasi isinya.
 */
class UpdateLandingContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:300'],
            'cards' => ['required', 'array', 'size:4'],
            'cards.*.label' => ['required', 'string', 'max:30'],
            'cards.*.title' => ['required', 'string', 'max:30'],
            'cards.*.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function attributes(): array
    {
        return [
            'headline' => 'judul beranda',
            'tagline' => 'tagline',
            'cards.*.label' => 'label banner',
            'cards.*.title' => 'judul banner',
            'cards.*.color' => 'warna banner',
        ];
    }
}