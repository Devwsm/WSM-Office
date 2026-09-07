<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model RoyaltyEntry
 * ---------------------------------------------------------------------
 * Fase 13 — padanan `state.royaltyEntries` (`saveRoyaltyEntry`) di
 * prototype v18. Nempel modul `royalty` ("Royalty Dashboard"). Lihat
 * catatan di migration soal `state.royaltyFinance` (subsistem lebih
 * detail v15) yang SENGAJA gak diikutin di sini.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'title',
    'period',
    'source',
    'status',
    'gross',
    'share_pct',
    'recoup',
    'note',
    'updated_by',
])]
class RoyaltyEntry extends Model
{
    public const STATUSES = ['Estimated', 'Reported', 'Ready to Pay', 'Paid'];

    protected function casts(): array
    {
        return [
            'gross' => 'float',
            'share_pct' => 'float',
            'recoup' => 'float',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Net payable = (gross * share%) - recoup — sama formula `royaltyNet()` di prototype. */
    public function net(): float
    {
        $share = (float) $this->gross * ((float) $this->share_pct / 100);

        return round($share - (float) $this->recoup, 2);
    }
}