<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model MeetingActionItem
 * ---------------------------------------------------------------------
 * Fase 9 — padanan `momActionRow()` di prototype v18. `pic_all` dipisah
 * dari `pic_employee_id` (bukan nilai sentinel 'all' di kolom FK) buat
 * opsi PIC "ALL TEAM".
 *
 * `workItem()` — relasi balik ke `work_items` KALAU action item ini
 * udah di-generate jadi task tracker (checkbox "masukkan ke Work
 * Tracker" pas Save MoM). TAMBAHAN di luar prototype: prototype cuma
 * nulis teks "From MoM: ..." di notes task, gak ada relasi beneran.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'meeting_id',
    'task',
    'pic_employee_id',
    'pic_all',
    'due_date',
])]
class MeetingActionItem extends Model
{
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'pic_all' => 'boolean',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_employee_id');
    }

    public function workItem(): HasOne
    {
        return $this->hasOne(WorkItem::class);
    }
}