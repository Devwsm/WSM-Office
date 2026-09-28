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
            'project_id' => ['required', 'exists:projects,id'],
            'section' => ['required', 'string', 'max:80'],
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

    /**
     * 2026-09-28 — task WAJIB punya project & section (3 lapis: project >
     * section > item). Form cuma menawarkan yang sudah ada (atau buat
     * section baru), jadi kalau sampai kosong berarti request akal-akalan
     * / form basi — tolak, jangan diam-diam bikin task "Tanpa Project".
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'Pilih project dulu. Belum ada project? Buat dulu di menu Projects.',
            'project_id.exists' => 'Project yang dipilih tidak ditemukan.',
            'section.required' => 'Pilih section, atau buat section baru untuk project ini.',
        ];
    }
}