<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Orang yang boleh melihat section ini (2026-10-03). Kosong = terbuka
     * untuk semua; terisi = terbatas (lihat migration project_section_viewers).
     */
    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_section_viewers', 'project_section_id', 'user_id');
    }

    public function isRestricted(): bool
    {
        return $this->viewers->isNotEmpty();
    }

    /**
     * Boleh membuka section ini? Owner/Developer selalu boleh. Section tanpa
     * viewer terbuka untuk semua. Section terbatas hanya untuk viewer yang
     * dipilih DAN siapa pun yang menjadi PIC (utama/tambahan) di salah satu
     * item section itu — PIC otomatis ikut daftar viewer (2026-10-14).
     * `$picIds` boleh diberikan kalau sudah dihitung (hindari query per section).
     *
     * @param  list<int>|null  $picIds
     */
    public function isVisibleTo(User $user, ?array $picIds = null): bool
    {
        if ($user->seesAllWork() || ! $this->isRestricted()) {
            return true;
        }

        return $this->viewers->contains('id', $user->id)
            || in_array($user->id, $picIds ?? $this->picUserIds(), true);
    }

    /** ID semua PIC (utama + tambahan) dari item di section ini. @return list<int> */
    public function picUserIds(): array
    {
        return static::picMap()[static::picKey($this->project_id, $this->name)] ?? [];
    }

    public static function picKey(?int $projectId, ?string $name): string
    {
        return (int) $projectId . '|' . $name;
    }

    /**
     * Peta "project_id|nama section" => daftar ID PIC, dihitung sekali untuk
     * semua section (1 query). Dipakai tracker untuk menandai PIC otomatis
     * sebagai viewer dan menentukan section mana yang terkunci.
     *
     * @return array<string, list<int>>
     */
    public static function picMap(): array
    {
        $main = DB::table('work_items')
            ->whereNotNull('project_id')->where('section', '!=', '')->whereNotNull('pic_employee_id')
            ->select('project_id', 'section', 'pic_employee_id as user_id');

        $rows = DB::table('work_item_additional_pics as a')
            ->join('work_items as w', 'w.id', '=', 'a.work_item_id')
            ->whereNotNull('w.project_id')->where('w.section', '!=', '')
            ->select('w.project_id', 'w.section', 'a.user_id')
            ->union($main)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[static::picKey((int) $row->project_id, (string) $row->section)][(int) $row->user_id] = (int) $row->user_id;
        }

        return array_map('array_values', $map);
    }

    /**
     * Cek akses ke section lewat (project, nama) — dipakai endpoint tulis.
     * Section yang tidak terdaftar / tanpa project / tanpa nama = terbuka.
     */
    public static function userHasAccess(User $user, ?int $projectId, ?string $name): bool
    {
        $name = trim((string) $name);

        if ($user->seesAllWork() || ! $projectId || $name === '') {
            return true;
        }

        $section = static::query()->with('viewers:id')
            ->where('project_id', $projectId)->where('name', $name)->first();

        return $section === null || $section->isVisibleTo($user);
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