<?php

namespace App\Http\Requests\Dashboard\Work;

use App\Models\WorkItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * WorkItemRequest
 * ---------------------------------------------------------------------
 * Fase 9 lanjutan (audit ronde 6, 2026-09-09) — dipakai buat store &
 * update WorkItem dari board Work Tracker. `focus` SENGAJA gak ada di
 * sini — kolom itu cuma dipakai kalau mau OVERRIDE hitungan otomatis
 * `computedFocus()` (lihat model), board ini gak expose override itu,
 * jadi selalu null/auto, sama kayak card di App Mode Home.
 * `item_no` juga gak divalidasi dari form — auto-increment per section
 * per project, diisi controller (padanan nomor urut tracker manual di
 * prototype).
 * ---------------------------------------------------------------------
 */
class WorkItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'section' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'pic_employee_id' => ['nullable', 'exists:users,id'],
            'additional_pic' => ['nullable', 'string', 'max:255'],
            'progress' => ['required', Rule::in(WorkItem::PROGRESS_OPTIONS)],
            'priority' => ['required', Rule::in(WorkItem::PRIORITIES)],
            'notes' => ['nullable', 'string'],
            'link' => ['nullable', 'url', 'max:255'],
            'is_reminder' => ['sometimes', 'boolean'],
        ];
    }
}