<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreJobApplicationRequest
 * ---------------------------------------------------------------------
 * Validasi form lamaran publik di halaman detail lowongan (Fase 3).
 * Masih teks saja sesuai keputusan awal — nama, email, telepon (opsional),
 * pesan/motivasi. Upload CV menyusul nanti sebagai kolom tambahan, bukan
 * di request ini biar migration-nya rapi (nggak perlu ubah struktur pas
 * fitur upload ditambah).
 * ---------------------------------------------------------------------
 */
class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi supaya kami bisa menghubungi balik.',
        ];
    }
}