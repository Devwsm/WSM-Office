<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ClockOutRequest
 * ---------------------------------------------------------------------
 * Validasi absen pulang (Fase 4). Sama strukturnya dengan ClockInRequest,
 * dipisah jadi request sendiri biar kalau nanti aturan check-out beda
 * (mis. wajib isi catatan kalau pulang cepat) tinggal ubah di sini tanpa
 * ganggu validasi check-in.
 * ---------------------------------------------------------------------
 */
class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'photo' => ['nullable', 'string', 'regex:/^data:image\/(jpeg|jpg|png|webp);base64,/', 'max:3000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.required' => 'Lokasi belum kebaca. Coba "Test Lokasi" dulu atau izinkan akses lokasi di browser.',
            'lng.required' => 'Lokasi belum kebaca. Coba "Test Lokasi" dulu atau izinkan akses lokasi di browser.',
            'photo.regex' => 'Format foto tidak dikenali, coba ambil ulang.',
            'photo.max' => 'Ukuran foto terlalu besar.',
        ];
    }
}