<?php

namespace App\Http\Requests\Dashboard\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdatePayrollRecordRequest
 * ---------------------------------------------------------------------
 * Fase 12 — validasi penyesuaian manual 1 baris payroll
 * (`other_adjustment` + `notes`). SENGAJA cuma 2 field ini yang bisa
 * diedit lewat form — `base_salary`/`overtime_amount`/`shortage_deduction`
 * murni hasil hitungan `PayrollController::generate()`, kalau mau
 * dikoreksi jalannya lewat regenerate (ubah data sumbernya: gaji
 * karyawan / status lembur / rekap absensi), bukan diketik ulang manual
 * di sini — biar angka tetap bisa ditelusuri balik ke sumbernya.
 * Controller yang mastiin cuma record berstatus `draft` yang boleh
 * lewat request ini (lihat `PayrollController::update()`).
 * ---------------------------------------------------------------------
 */
class UpdatePayrollRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'other_adjustment' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}