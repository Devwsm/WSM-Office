<?php

namespace App\Http\Requests\Memo;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ReplyMemoThreadRequest
 * ---------------------------------------------------------------------
 * Fase 8 — dipakai bareng sama 2 controller (Employee\MemoInteractionController
 * & Dashboard\Work\MemoController::reply) karena validasinya identik,
 * cuma beda siapa yang boleh akses (dicek middleware/role di
 * masing-masing route, bukan di sini).
 * ---------------------------------------------------------------------
 */
class ReplyMemoThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}