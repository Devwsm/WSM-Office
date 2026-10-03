<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload foto profil sendiri (tombol "Ganti Foto" di tab Profile).
 * Batas 2 MB dan hanya JPG/PNG/WebP — foto kamera HP biasanya lebih besar,
 * jadi pesan errornya menyebut batas itu secara eksplisit.
 */
class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Pilih foto dulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'photo.uploaded' => 'Foto gagal diunggah. Coba foto yang ukurannya lebih kecil (maksimal 2 MB).',
        ];
    }
}