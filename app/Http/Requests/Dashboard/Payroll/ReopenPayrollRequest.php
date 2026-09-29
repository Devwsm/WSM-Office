<?php

namespace App\Http\Requests\Dashboard\Payroll;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Buka kembali payroll Final: HANYA Owner (Developer tidak), alasan wajib.
 */
class ReopenPayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isOwner();
    }

    public function rules(): array
    {
        return [
            'reopen_reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reopen_reason.required' => 'Alasan membuka kembali payroll wajib diisi.',
            'reopen_reason.min' => 'Alasan minimal 5 karakter.',
        ];
    }
}