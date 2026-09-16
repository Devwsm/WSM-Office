<?php

namespace App\Http\Requests\Employee;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * StoreAttendanceCorrectionRequestRequest
 * ---------------------------------------------------------------------
 * 2026-09-16 — validasi pengajuan Koreksi Presensi baru. Beda dari
 * StoreLeaveRequestRequest (`after_or_equal:today`), di sini
 * `before_or_equal:today` — koreksi presensi itu buat memperbaiki hari
 * yang UDAH lewat/hari ini, bukan buat masa depan.
 *
 * `requested_clock_in`/`requested_clock_out` minimal salah satu wajib
 * diisi (divalidasi lewat withValidator(), bukan aturan per-field biasa)
 * — kalau dua-duanya kosong, nggak ada yang mau dikoreksi.
 * ---------------------------------------------------------------------
 */
class StoreAttendanceCorrectionRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'requested_clock_in' => ['nullable', 'date_format:H:i'],
            'requested_clock_out' => ['nullable', 'date_format:H:i'],
            'requested_mode' => ['required', 'in:' . implode(',', Attendance::ALL_MODES)],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('requested_clock_in') && ! $this->filled('requested_clock_out')) {
                $validator->errors()->add(
                    'requested_clock_in',
                    'Isi minimal salah satu: jam masuk atau jam pulang yang seharusnya.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'Koreksi presensi cuma buat tanggal hari ini atau sebelumnya.',
        ];
    }
}