<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Meeting
 * ---------------------------------------------------------------------
 * Fase 9 — Minutes of Meeting, padanan `state.meetings` (`saveMom`) di
 * prototype v18. Attendee lewat pivot `meeting_attendees`, action item
 * di tabel terpisah `meeting_action_items` (bisa jadi `work_items`
 * kalau dicentang "masukkan ke Work Tracker" pas disimpan controller).
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'project_id',
    'date',
    'time',
    'agenda',
    'persons_text',
    'notes',
    'decisions',
    'blasted_at',
    'created_by',
])]
class Meeting extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'blasted_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_attendees');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingActionItem::class);
    }

    public function wasBlasted(): bool
    {
        return $this->blasted_at !== null;
    }
}