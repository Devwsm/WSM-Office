<?php

namespace App\Http\Requests\Employee;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * StoreLeaveRequestRequest
 * ---------------------------------------------------------------------
 * Fase 5 — validasi pengajuan izin/cuti baru. `after_or_equal:today`
 * karena kesepakatan Fase 5: nggak bisa ajukan buat tanggal yang udah
 * lewat (beda kasus sama koreksi absen yang emang buat masa lalu).
 * Validasi saldo cuti tahunan dilakukan lewat withValidator() karena
 * butuh hitung work_days dulu dari start_date/end_date, bukan aturan
 * per-field biasa.
 * ---------------------------------------------------------------------
 */
class StoreLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:' . implode(',', LeaveRequest::TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('type') || ! $this->filled('start_date') || ! $this->filled('end_date')) {
                return;
            }

            if ($this->input('type') !== LeaveRequest::QUOTA_TYPE) {
                return;
            }

            $workDays = LeaveRequest::countWorkDays($this->input('start_date'), $this->input('end_date'));
            $remaining = $this->user()->remainingAnnualLeaveDays();

            if ($workDays > $remaining) {
                $validator->errors()->add(
                    'start_date',
                    "Sisa jatah cuti tahunan kamu tinggal {$remaining} hari kerja, pengajuan ini butuh {$workDays} hari kerja."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Tanggal mulai nggak boleh tanggal yang udah lewat.',
            'end_date.after_or_equal' => 'Tanggal selesai nggak boleh sebelum tanggal mulai.',
        ];
    }
}