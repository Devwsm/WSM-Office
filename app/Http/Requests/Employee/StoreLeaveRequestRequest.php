<?php

namespace App\Http\Requests\Employee;

use App\Models\OvertimeRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Validator;

/**
 * StoreOvertimeRequestRequest
 * ---------------------------------------------------------------------
 * Fase 7 — validasi pengajuan Lembur baru. `after_or_equal:today` sama
 * alasan kayak StoreLeaveRequestRequest (Fase 5): gak bisa ajukan buat
 * tanggal yang udah lewat. Cek duplikat (1 user cuma boleh 1 pengajuan
 * AKTIF per tanggal, sesuai `unique(user_id,date)` di migration) lewat
 * `withValidator()` karena butuh exclude pengajuan yang statusnya udah
 * 'ditolak'/'dibatalkan' (constraint DB `unique` gak bisa syarat
 * "kecuali status tertentu").
 * ---------------------------------------------------------------------
 */
class StoreOvertimeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('date')) {
                return;
            }

            $exists = OvertimeRequest::query()
                ->where('user_id', Auth::id())
                ->whereDate('date', $this->input('date'))
                ->whereIn('status', ['pending', 'disetujui'])
                ->exists();

            if ($exists) {
                $validator->errors()->add('date', 'Kamu sudah punya pengajuan lembur aktif di tanggal ini.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Tanggal lembur nggak boleh tanggal yang udah lewat.',
        ];
    }
}