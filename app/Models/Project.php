<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Project
 * ---------------------------------------------------------------------
 * Fase 9 — project master. `work_items`/`meetings`/`project_budgets`
 * (Fase 13) semua nunjuk balik ke sini lewat `project_id` nullable.
 * ---------------------------------------------------------------------
 */
#[Fillable([
    'slug',
    'name',
    'color',
    'start_date',
    'end_date',
    'priority',
    'status',
    'lead_employee_id',
    'tracker_url',
    'progress_recap',
    'created_by',
])]
class Project extends Model
{
    public const PRIORITIES = ['Low', 'Medium', 'High'];

    public const STATUSES = ['On Development', 'Follow Up', 'Done', 'Postpone', 'Pending', 'Confirmed'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * ⚠️ 2026-09-09 — pas `migrate:fresh --seed` beneran dicoba, hook
     * ini TERNYATA gak keisi ke query INSERT waktu dipanggil lewat
     * `Project::create([...])` tanpa `slug` di array-nya (lihat
     * README "My Work Tracker + Shared Calendar" buat detail error &
     * fix sementara di DemoSeeder). Akar masalahnya BELUM
     * dikonfirmasi — `#[Fillable(['slug', ...])]` di atas class ini
     * memang class asli Laravel 13, bukan salah pakai, jadi bukan itu
     * penyebabnya. SAMPAI ini diinvestigasi & diperbaiki, JANGAN
     * andelin hook ini — selalu isi `slug` eksplisit tiap kali bikin
     * Project baru (`Project::uniqueSlugFrom($name)`), sama pola yang
     * dipakai `JobOpeningController::store()`.
     */
    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (! $project->slug) {
                $project->slug = static::uniqueSlugFrom($project->name);
            }

            // 2026-09-16 — kalau form gak isi warna (mis. lewat Import
            // XLS/CSV nanti, atau field-nya dikosongin), tetap kasih
            // warna dari palet default biar kanban/kalender selalu ada
            // warnanya — bukan asal abu netral (itu khusus NO_PROJECT_COLOR
            // buat task yang project_id-nya NULL).
            if (! $project->color) {
                $project->color = static::nextPaletteColor();
            }
        });
    }

    public static function uniqueSlugFrom(string $name): string
    {
        $base = Str::slug($name) ?: 'project';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$i;
        }

        return $slug;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workItems(): HasMany
    {
        return $this->hasMany(WorkItem::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    /** Total budget vs actual seluruh line item project ini (Fase 13). */
    public function budgetTotals(): array
    {
        $budget = (float) $this->budgets()->sum('budget');
        $actual = (float) $this->budgets()->sum('actual');

        return [
            'budget' => $budget,
            'actual' => $actual,
            'remaining' => $budget - $actual,
        ];
    }

    /**
     * 2026-09-16 — "Warna Project" (temuan audit prototype: form "Create
     * Project" prototype punya field Project Color hex+picker, WSM-Office
     * belum punya kolom `color` sama sekali — makanya warna project di
     * Timeline Calendar & Work Tracker board SEBELUMNYA hardcoded/
     * deterministik dari `$projectId % count($palette)`, BUKAN warna
     * pilihan user beneran). Dipakai ganti PROJECT_COLOR_PALETTE yang
     * dulu ada duplikat identik di Dashboard\Work\CalendarController &
     * Employee\WorkTrackerController — sekarang cukup baca `$project->color`.
     */
    private const DEFAULT_COLOR_PALETTE = [
        '#3558f4', // brand-blue
        '#deb92e', // brand-yellow
        '#27c84d', // brand-green
        '#b4ef4b', // brand-lime
        '#f16c61', // brand-red
        '#6e95f5', // brand-blue-light
        '#f3e65c', // brand-yellow-light
    ];

    /** Warna abu netral buat task TANPA project ("Tanpa Project") — bukan pilihan user, jadi bukan bagian dari DEFAULT_COLOR_PALETTE. */
    public const NO_PROJECT_COLOR = '#8b867e';

    /**
     * Dipanggil pas project baru dibuat TANPA `color` eksplisit dari form
     * (mis. lewat Import XLS/CSV nanti, atau kalau user ngosongin field
     * warnanya) — assign dari palet default secara berurutan (bukan
     * random) berdasar jumlah project yang udah ada, biar tiap project
     * baru cenderung beda warna dari yang sebelumnya.
     */
    public static function nextPaletteColor(): string
    {
        $count = static::query()->count();

        return self::DEFAULT_COLOR_PALETTE[$count % count(self::DEFAULT_COLOR_PALETTE)];
    }

    /** Warna project ini, atau abu netral kalau $this null (task tanpa project) — dipanggil statis lewat Project::colorFor($item->project). */
    public static function colorFor(?self $project): string
    {
        return $project->color ?? self::NO_PROJECT_COLOR;
    }

    /** Hitamin atau putihin teks di atas warna project ini, biar kebaca (WCAG-ish luminance check sederhana) — dipakai buat badge/card warna project. */
    public static function contrastTextFor(string $hexColor): string
    {
        $hex = ltrim($hexColor, '#');
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.6 ? '#17130a' : '#ffffff';
    }
}