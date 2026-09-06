<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * CorrectAttendanceRequest
 * ---------------------------------------------------------------------
 * Fase 5 — koreksi absen manual. Kesepakatan: cuma edit JAM (masuk/
 * pulang), BUKAN override status jadi izin/cuti manual (itu harus lewat
 * alur pengajuan resmi di LeaveRequest). Minimal salah satu dari
 * clock_in_time/clock_out_time harus diisi — divalidasi lewat
 * withValidator() karena aturannya "minimal salah satu", bukan
 * required biasa.
 * ---------------------------------------------------------------------
 */
class CorrectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in_time' => ['nullable', 'date_format:H:i'],
            'clock_out_time' => ['nullable', 'date_format:H:i'],
            'correction_note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('clock_in_time') && ! $this->filled('clock_out_time')) {
                $validator->errors()->add('clock_in_time', 'Isi minimal salah satu: jam masuk atau jam pulang.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'correction_note.required' => 'Catatan alasan koreksi wajib diisi, biar transparan ke karyawan.',
        ];
    }
}