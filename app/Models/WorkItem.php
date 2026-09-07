<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Model WorkItem
 * ---------------------------------------------------------------------
 * Fase 9 — padanan "Item/Task" (`state.tasks`) di prototype v18, nempel
 * modul `work` (Work Control). `focus` ("HARI INI"/"BESOK"/"KELEWAT"/
 * dst) SENGAJA gak disimpan hasil hitungnya di kolom `focus` kalau
 * kosong — dihitung on-the-fly lewat `computedFocus()` dari `due_date`,
 * sama prinsip kayak status Attendance yang gak disimpan biar gak basi.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'project_id',
    'meeting_action_item_id',
    'section',
    'item_no',
    'title',
    'due_date',
    'focus',
    'pic_employee_id',
    'additional_pic',
    'progress',
    'priority',
    'notes',
    'link',
    'is_reminder',
    'created_by',
])]
class WorkItem extends Model
{
    public const SECTION_SUGGESTIONS = [
        'RELEASE DAY',
        'SCHEDULE PENTING',
        'COLLABORATORS',
        'CONTRACT',
        'BISNIS PROPOSAL',
        'BUDGETING',
        'SONG',
        'AUDIO',
        'DOCUMENTARY',
        'RELEASE PLAN',
        'PRESS CONFERENCE & LISTENING SESSION',
        'MARKETING',
        'LEGAL',
        'FINANCE',
        'OPERATIONS',
        'CREATIVE',
        'GENERAL AFFAIRS',
        'REMINDER / ADMIN',
        'OTHER',
    ];

    public const PROGRESS_OPTIONS = ['Pending', 'On Development', 'Follow Up', 'Confirmed', 'Done', 'Postpone'];

    public const PRIORITIES = ['Low', 'Medium', 'High'];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_reminder' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meetingActionItem(): BelongsTo
    {
        return $this->belongsTo(MeetingActionItem::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Padanan logic "Auto" focus di prototype (`calCategory`/focus
     * badge) — dipakai kalau kolom `focus` kosong. Item yang udah Done
     * SENGAJA dianggap "SELESAI" terlepas dari due date-nya.
     */
    public function computedFocus(): string
    {
        if ($this->focus) {
            return $this->focus;
        }

        if ($this->progress === 'Done') {
            return 'SELESAI';
        }

        if (! $this->due_date) {
            return 'NOT URGENT';
        }

        $today = Carbon::today();
        $due = $this->due_date->copy()->startOfDay();

        if ($due->lt($today)) {
            return 'KELEWAT';
        }
        if ($due->isSameDay($today)) {
            return 'HARI INI';
        }
        if ($due->isSameDay($today->copy()->addDay())) {
            return 'BESOK';
        }
        if ($due->isSameWeek($today)) {
            return 'MINGGU INI';
        }
        if ($due->isSameWeek($today->copy()->addWeek())) {
            return 'MINGGU DEPAN';
        }

        return 'AMAN';
    }

    public function isOverdue(): bool
    {
        return $this->progress !== 'Done'
            && $this->due_date
            && $this->due_date->copy()->startOfDay()->lt(Carbon::today());
    }
}