<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ClockInRequest
 * ---------------------------------------------------------------------
 * Validasi absen masuk (Fase 4). `photo` dikirim sebagai base64 data URL
 * (bukan file upload biasa) karena diambil & dikompres di sisi browser
 * lewat <canvas> sebelum submit — lihat resources/js/attendance.js.
 * Foto sifatnya opsional (sesuai keputusan awal Fase 4).
 * ---------------------------------------------------------------------
 */
class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:kantor,wfh'],
            'work_context' => ['nullable', 'string', 'max:150'],
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