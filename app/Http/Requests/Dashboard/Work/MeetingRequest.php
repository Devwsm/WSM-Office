<?php

namespace App\Http\Requests\Dashboard\Work;

use Illuminate\Foundation\Http\FormRequest;

/**
 * MeetingRequest
 * ---------------------------------------------------------------------
 * Fase 9 lanjutan (MoM terstruktur) — dipakai buat store & update
 * `Meeting` + `MeetingActionItem` sekaligus dalam 1 form (padanan form
 * MoM prototype v18 yang juga satu layar: agenda, attendees, notes,
 * decisions, action items).
 *
 * `attendees` di sini array of user id (checkbox, di-sync ke pivot
 * `meeting_attendees` oleh controller) — BEDA dari `persons_text`
 * (freeform, buat peserta di luar sistem kayak klien/tamu, padanan
 * kolom `attendees` di tabel `memos` yang juga freeform).
 *
 * `action_items.*.id` SENGAJA nullable/tidak divalidasi ketat — cuma
 * dipakai controller buat bedain "update baris lama" vs "baris baru"
 * pas edit, bukan input asli dari user (di-render sebagai hidden field
 * per baris di form).
 * ---------------------------------------------------------------------
 */
class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:work,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,id'],
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'agenda' => ['required', 'string', 'max:200'],
            'persons_text' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'decisions' => ['nullable', 'string'],

            'attendees' => ['nullable', 'array'],
            'attendees.*' => ['integer', 'exists:users,id'],

            'action_items' => ['nullable', 'array'],
            'action_items.*.id' => ['nullable', 'integer', 'exists:meeting_action_items,id'],
            'action_items.*.task' => ['required', 'string', 'max:255'],
            'action_items.*.pic_employee_id' => ['nullable', 'integer', 'exists:users,id'],
            'action_items.*.pic_all' => ['sometimes', 'boolean'],
            'action_items.*.due_date' => ['nullable', 'date'],

            'sync_to_tracker' => ['sometimes', 'boolean'],
        ];
    }
}