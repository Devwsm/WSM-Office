<?php

namespace App\Http\Requests\Dashboard\Royalty;

use App\Models\RoyaltyEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * RoyaltyRequest
 * ---------------------------------------------------------------------
 * Fase 13 — dipakai buat store & update RoyaltyEntry. Cuma level
 * `royaltyEntries` prototype v18 yang di-porting (title/period/source/
 * status/gross/share_pct/recoup/note) — SENGAJA gak termasuk subsistem
 * `royaltyFinance` (revenue ledger per-lagu) yang lebih detail, lihat
 * catatan lengkap di migration `create_royalty_entries_table`.
 * ---------------------------------------------------------------------
 */
class RoyaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:royalty,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'period' => ['nullable', 'date_format:Y-m'],
            'source' => ['nullable', 'string', 'max:150'],
            'status' => ['required', Rule::in(RoyaltyEntry::STATUSES)],
            'gross' => ['required', 'numeric', 'min:0'],
            'share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'recoup' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}