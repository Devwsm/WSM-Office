<?php

namespace App\Http\Requests\Dashboard\Budget;

use Illuminate\Foundation\Http\FormRequest;

/**
 * BudgetRequest
 * ---------------------------------------------------------------------
 * Fase 13 — dipakai buat store & update ProjectBudget. `category`
 * SENGAJA input teks bebas (bukan dropdown/enum) — persis prototype
 * v18 (lihat catatan migration `create_project_budgets_table`); form
 * hanya memberi saran lewat datalist. Pengelompokan di grafik tidak
 * membedakan huruf besar/kecil, jadi "Marketing" dan "marketing" tetap
 * satu batang.
 *
 * `song_title` teks bebas (belum ada entitas lagu), `proof_link` harus
 * http(s) — link tanpa skema (mis. `drive.google.com/...`) otomatis
 * dilengkapi `https://`, skema lain (javascript:, ftp:, dst.) ditolak
 * karena link ini dirender sebagai <a href>.
 * ---------------------------------------------------------------------
 */
class BudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:budget,manage sudah jaga route-nya.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $link = $this->input('proof_link');

        if (is_string($link)) {
            $link = trim($link);

            // Hanya tambahkan https:// kalau memang tidak ada skema sama sekali.
            // "javascript:alert(1)" / "ftp://x" punya skema, jadi dibiarkan dan
            // ditolak aturan url:http,https di bawah.
            if ($link !== '' && ! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $link)) {
                $link = 'https://' . ltrim($link, '/');
            }

            $this->merge(['proof_link' => $link]);
        }
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'category' => ['required', 'string', 'max:100'],
            'item' => ['required', 'string', 'max:150'],
            'song_title' => ['nullable', 'string', 'max:150'],
            'budget' => ['required', 'numeric', 'min:0'],
            'actual' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'proof_link' => ['nullable', 'url:http,https', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'song_title' => 'lagu',
            'proof_link' => 'link bukti bayar',
        ];
    }
}