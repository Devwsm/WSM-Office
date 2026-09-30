<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** PIC utama + maksimal 2 PIC tambahan (padanan "maksimal 3 PIC" di prototype). */
    public const MAX_ADDITIONAL_PICS = 2;

    /**
     * Kolom `section` NOT NULL, tapi form Task, sinkron action item MoM, dan
     * import boleh mengosongkannya (null) — tanpa ini insert gagal dengan
     * error 500. Section kosong disimpan sebagai string kosong.
     */
    protected function section(): Attribute
    {
        return Attribute::make(set: fn(?string $value) => $value ?? '');
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_reminder' => 'boolean',
        ];
    }

    /**
     * Item yang memakai section baru di sebuah project otomatis mendaftarkan
     * section itu ke `project_sections` (warna & urutan) — satu tempat untuk
     * semua jalur tulis: form task, import, action item MoM, reminder.
     */
    protected static function booted(): void
    {
        static::saved(function (self $item) {
            if ($item->wasRecentlyCreated || $item->wasChanged(['project_id', 'section'])) {
                ProjectSection::ensure($item->project_id, $item->section);
            }
        });
    }

    /**
     * Task yang melibatkan $userId sebagai PIC — PIC utama ATAU salah satu PIC
     * tambahan. Satu-satunya tempat definisi "task milik seseorang", dipakai
     * Home karyawan, kalender, Team Overview, dan filter PIC di board.
     */
    public function scopeForPic(Builder $query, int|array|Collection $userIds): Builder
    {
        $ids = collect(is_iterable($userIds) ? $userIds : [$userIds])->filter()->values()->all();

        return $query->where(function (Builder $w) use ($ids) {
            $w->whereIn('work_items.pic_employee_id', $ids)
                ->orWhereIn('work_items.id', DB::table('work_item_additional_pics')
                    ->whereIn('user_id', $ids)
                    ->select('work_item_id'));
        });
    }

    /**
     * Task yang boleh dilihat $user di kalender bersama karyawan: task
     * tanpa project, task di project yang terlihat olehnya (lihat
     * Project::scopeVisibleTo), atau task yang dia sendiri PIC-nya
     * (PIC utama, PIC tambahan, atau namanya tertulis di catatan teks
     * `additional_pic`). Dengan begitu pembatasan project tidak pernah
     * menyembunyikan task milik sendiri.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->seesAllWork()) {
            return $query;
        }

        $nameLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $user->name) . '%';

        return $query->where(function (Builder $w) use ($user, $nameLike) {
            $w->whereNull('project_id')
                ->orWhereIn('project_id', Project::query()->visibleTo($user)->select('projects.id'))
                ->orWhere(fn(Builder $own) => $own->forPic($user->id))
                ->orWhere('additional_pic', 'like', $nameLike);
        });
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

    /** PIC 2 & PIC 3 (relasi sungguhan ke user, beda dari teks bebas `additional_pic`). */
    public function additionalPics(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'work_item_additional_pics', 'work_item_id', 'user_id')
            ->withTimestamps()
            ->orderBy('work_item_additional_pics.id');
    }

    /**
     * Semua PIC berurutan: PIC utama dulu, lalu tambahan. Butuh relasi `pic`
     * & `additionalPics` sudah di-eager-load kalau dipakai di loop.
     *
     * @return Collection<int, User>
     */
    public function allPics(): Collection
    {
        return collect([$this->pic])->concat($this->additionalPics)->filter()->unique('id')->values();
    }

    public function hasPic(int $userId): bool
    {
        return $this->allPics()->contains('id', $userId);
    }

    /** Label PIC untuk tampilan ringkas, mis. "Ancha · Kanaya" (pola `trackerOwnerName()` prototype). */
    public function picLabel(string $fallback = 'Belum di-assign'): string
    {
        $names = $this->allPics()->pluck('name');

        return $names->isNotEmpty() ? $names->implode(' · ') : $fallback;
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