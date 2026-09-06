<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CancelLeaveRequestRequest
 * ---------------------------------------------------------------------
 * Fase 5 — dipakai bareng oleh Employee\LeaveRequestController::cancel()
 * (karyawan batalkan pengajuannya sendiri) dan
 * Approval\LeaveRequestController::cancel() (Manajer/Owner batalkan
 * izin/cuti yang udah disetujui) — sengaja bukan sub-namespace Employee
 * atau Approval karena aturannya (wajib alasan) sama persis buat
 * keduanya.
 * ---------------------------------------------------------------------
 */
class CancelLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'Alasan pembatalan wajib diisi.',
        ];
    }
}