<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Model ProjectSection
 * ---------------------------------------------------------------------
 * 2026-09-30 — pengaturan tampilan 1 section di dalam 1 project: warna
 * & urutan (padanan `projectSectionOrderV24`/`sectionColors` prototype).
 *
 * `work_items.section` tetap string bebas; tabel ini HANYA menyimpan
 * warna/urutan, dan dijaga otomatis:
 *  - WorkItem::saved membuat baris kalau item baru memakai section baru
 *    (jadi import, MoM, reminder, form task — semua ikut),
 *  - syncMissing() menambal sisanya saat halaman Work Tracker dibuka
 *    (mis. item yang dimasukkan lewat query langsung).
 * Section yang terlanjur tidak punya baris tetap tampil, hanya tanpa
 * tombol atur — halaman tidak pernah bergantung pada baris ini ada.
 * ---------------------------------------------------------------------
 */
#[Fillable(['project_id', 'name', 'color', 'sort_order'])]
class ProjectSection extends Model
{
    /** Warna default (pastel) — dulu hardcode di view tracker, urutan & isinya TIDAK boleh berubah. */
    public const DEFAULT_PALETTE = [
        '#dbe8f7',
        '#f7e8a6',
        '#e4d8f2',
        '#f5cfc9',
        '#d5e4fb',
        '#f6d9a8',
        '#d4ecd0',
        '#cbe6e3',
        '#ddd6f3',
        '#f5d6ea',
        '#e7dcc0',
    ];

    /** Warna default dari nama — deterministik, sama persis dengan yang dilihat tim sebelum fitur ini. */
    public static function defaultColorFor(string $name): string
    {
        return self::DEFAULT_PALETTE[crc32($name) % count(self::DEFAULT_PALETTE)];
    }

    public function effectiveColor(): string
    {
        return $this->color ?: self::defaultColorFor($this->name);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Pastikan (project, nama) punya baris; baru ditaruh di urutan paling bawah. Idempotent. */
    public static function ensure(?int $projectId, ?string $name): void
    {
        $name = trim((string) $name);

        if (! $projectId || $name === '') {
            return;
        }

        $name = mb_substr($name, 0, 80);

        if (static::query()->where('project_id', $projectId)->where('name', $name)->exists()) {
            return;
        }

        DB::table('project_sections')->insertOrIgnore([
            'project_id' => $projectId,
            'name' => $name,
            'color' => null,
            'sort_order' => (int) static::query()->where('project_id', $projectId)->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Buat baris untuk section yang dipakai item tapi belum punya baris,
     * diurutkan menurut item pertamanya, ditaruh setelah section yang ada.
     */
    public static function syncMissing(): void
    {
        $known = static::query()->get(['project_id', 'name'])
            ->mapWithKeys(fn(self $s) => [$s->project_id . '|' . $s->name => true]);

        $pairs = DB::table('work_items')
            ->whereNotNull('project_id')
            ->where('section', '!=', '')
            ->selectRaw('project_id, section, MIN(id) as first_id')
            ->groupBy('project_id', 'section')
            ->orderBy('first_id')
            ->get()
            ->reject(fn($p) => $known->has($p->project_id . '|' . $p->section));

        if ($pairs->isEmpty()) {
            return;
        }

        $next = static::query()->selectRaw('project_id, MAX(sort_order) as top')
            ->groupBy('project_id')->pluck('top', 'project_id')->map(fn($v) => (int) $v)->all();

        $rows = [];
        foreach ($pairs as $pair) {
            $next[$pair->project_id] = ($next[$pair->project_id] ?? 0) + 1;
            $rows[] = [
                'project_id' => $pair->project_id,
                'name' => mb_substr($pair->section, 0, 80),
                'color' => null,
                'sort_order' => $next[$pair->project_id],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('project_sections')->insertOrIgnore($chunk);
        }
    }

    /**
     * Semua section 1 project, terurut, dengan sort_order dirapikan jadi
     * 1..n (mencegah angka ganda/bolong setelah hapus). Dipakai geser.
     *
     * @return Collection<int, self>
     */
    public static function orderedFor(int $projectId): Collection
    {
        $sections = static::query()->where('project_id', $projectId)
            ->orderBy('sort_order')->orderBy('id')->get();

        foreach ($sections as $i => $section) {
            if ($section->sort_order !== $i + 1) {
                $section->update(['sort_order' => $i + 1]);
            }
        }

        return $sections->values();
    }
}