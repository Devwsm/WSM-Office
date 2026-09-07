<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model EmployeeContract
 * ---------------------------------------------------------------------
 * Fase 11 — padanan `state.contracts` (`saveContract`) di prototype
 * v18. Nempel modul `contracts` ("Contract Monitoring") yang slotnya
 * udah ada dari Fase 6a. File di storage, path disimpan di `file_path`
 * (dipakai lewat `asset('storage/...')`).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'employee_id',
    'file_path',
    'original_filename',
    'mime_type',
    'size_bytes',
    'start_date',
    'end_date',
    'notes',
    'uploaded_by',
])]
class EmployeeContract extends Model
{
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'size_bytes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** Dipakai buat badge "Kontrak ≤30 hari" di dashboard Owner (padanan logic `expiring` di prototype). */
    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if (! $this->end_date) {
            return false;
        }

        return $this->end_date->between(Carbon::today(), Carbon::today()->addDays($withinDays));
    }
}