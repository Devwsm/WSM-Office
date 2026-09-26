<?php

namespace App\Http\Requests\Dashboard\Work;

use App\Models\WorkItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * WorkReminderRequest
 * ---------------------------------------------------------------------
 * README Bab 2.1 #28 / Bab 4.2 no. 9 — "Assign / Reminder dari
 * dashboard". Padanan form "Send Reminder" di secretary-console /
 * adminWorkMemos prototype v32 (sendAdminReminder /
 * sendAdminDashboardReminder). Dipakai WorkReminderController::store(),
 * yang dari 1 submit ini bikin 2 record (WorkItem + Memo) — lihat
 * controller itu buat detailnya.
 * ---------------------------------------------------------------------
 */
class WorkReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:work,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'pic_employee_id' => ['required', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'priority' => ['required', Rule::in(WorkItem::PRIORITIES)],
            'notes' => ['nullable', 'string'],
        ];
    }
}