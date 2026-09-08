<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * UpdateOfficeSettingRequest
 * ---------------------------------------------------------------------
 * Fase 7 — validasi form Pengaturan Kantor (Owner). `normal_end_time`
 * harus lebih besar dari `work_start_time` (window kerja normal gak
 * mungkin kebalik) dicek lewat withValidator() karena butuh
 * bandingin 2 field, bukan aturan per-field biasa.
 *
 * (Dipindah 2026-09-08 ke file ini sendiri — sebelumnya kelas ini
 * kepakai ketuker di app/Http/Requests/Owner/UpdateEmployeeRequest.php,
 * jadi class ini sendiri gak pernah kebaca autoload Composer sesuai
 * nama file-nya.)
 * ---------------------------------------------------------------------
 */
class UpdateOfficeSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'office_name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'geo_attendance_enabled' => ['nullable', 'boolean'],
            'enforce_radius' => ['nullable', 'boolean'],
            'work_start_time' => ['required', 'date_format:H:i'],
            'normal_end_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'required_work_minutes' => ['required', 'integer', 'min:60', 'max:960'],
        ];
    }

    /** Checkbox yang gak dicentang gak dikirim browser sama sekali — normalisasi ke false eksplisit. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'geo_attendance_enabled' => $this->boolean('geo_attendance_enabled'),
            'enforce_radius' => $this->boolean('enforce_radius'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('work_start_time') || ! $this->filled('normal_end_time')) {
                return;
            }

            if ($this->input('normal_end_time') <= $this->input('work_start_time')) {
                $validator->errors()->add('normal_end_time', 'Jam selesai window kerja normal harus lebih besar dari jam mulai.');
            }
        });
    }
}