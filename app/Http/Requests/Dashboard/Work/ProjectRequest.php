<?php

namespace App\Http\Requests\Dashboard\Work;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ProjectRequest
 * ---------------------------------------------------------------------
 * Fase 9 lanjutan (audit ronde 6, 2026-09-09) — dipakai buat store &
 * update Project dari board Work Tracker (Dashboard > Work Control).
 * `slug` SENGAJA gak divalidasi/diterima dari form sama sekali —
 * Project::booted() yang generate otomatis dari `name`
 * (`Project::uniqueSlugFrom`), lihat catatan ⚠️ di model soal hook ini
 * TERNYATA gak jalan kalau `slug` gak ada di array `create([...])`,
 * jadi WorkTrackerController isi eksplisit, bukan cuma andelin hook.
 * ---------------------------------------------------------------------
 */
class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:work,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(Project::PRIORITIES)],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'lead_employee_id' => ['nullable', 'exists:users,id'],
            'tracker_url' => ['nullable', 'url', 'max:255'],
            'progress_recap' => ['nullable', 'string'],
        ];
    }
}