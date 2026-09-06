<?php

namespace App\Http\Requests\Employee;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ClockInRequest
 * ---------------------------------------------------------------------
 * Fase 4: validasi absen masuk. Fase 7: `mode` diperluas dari
 * `kantor,wfh` jadi 4 pilihan (`Attendance::ALL_MODES`) buat nampung
 * Lapangan/Gigs — dua mode itu yang boleh multi-sesi per hari (lihat
 * `AttendanceController::clockIn()`). Foto tetap opsional, sama Fase 4.
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
            'mode' => ['required', 'in:' . implode(',', Attendance::ALL_MODES)],
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