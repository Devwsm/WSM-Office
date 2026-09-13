<?php

namespace App\Http\Requests\Dashboard\Legal;

use App\Models\LegalDocument;
use Illuminate\Foundation\Http\FormRequest;

/**
 * LegalDocumentRequest
 * ---------------------------------------------------------------------
 * Fase 14 — dipakai buat store & update LegalDocument. Sama pola
 * persis ContractRequest (Fase 11): `file` cuma wajib pas STORE (POST),
 * pas UPDATE (PATCH) boleh gak upload ulang — file lama dipertahankan,
 * controller yang nentuin (lihat LegalController::update()).
 * ---------------------------------------------------------------------
 */
class LegalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Middleware module:legal,manage sudah jaga route-nya.
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', 'in:' . implode(',', LegalDocument::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'party' => ['nullable', 'string', 'max:255'],
            'file' => [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}