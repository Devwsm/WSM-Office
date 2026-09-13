<?php

namespace App\Http\Requests\Dashboard\It;

use App\Models\SystemChangelog;
use Illuminate\Foundation\Http\FormRequest;

/**
 * SystemChangelogRequest
 * ---------------------------------------------------------------------
 * Fase 15 — dipakai buat store & update SystemChangelog. `modules`
 * & `changes` tetap divalidasi sebagai STRING di sini (input form
 * teks biasa — modules dipisah koma, changes 1 baris = 1 bullet) —
 * di-split jadi array di controller SETELAH validasi, sama persis
 * behaviour prototype (`saveCustomChangeLog`).
 * ---------------------------------------------------------------------
 */
class SystemChangelogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:it,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'version' => ['required', 'string', 'max:50'],
            'release_date' => ['required', 'date'],
            'status' => ['required', 'in:' . implode(',', SystemChangelog::STATUSES)],
            'modules' => ['nullable', 'string', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'changes' => ['required', 'string'],
        ];
    }
}