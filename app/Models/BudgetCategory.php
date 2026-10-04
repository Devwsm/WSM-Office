<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Model BudgetCategory
 * ---------------------------------------------------------------------
 * 2026-10-04 — padanan ProjectSection untuk Project Budgeting: warna,
 * urutan, dan akses per orang untuk 1 kategori di dalam 1 project.
 *
 * `project_budgets.category` tetap string bebas; tabel ini hanya menyimpan
 * pengaturan tampilan/akses. Nama dicocokkan TANPA membedakan huruf
 * besar/kecil dan spasi pinggir ("Marketing" = "marketing "), sama dengan
 * pengelompokan di BudgetReport. Dijaga otomatis:
 *  - ProjectBudget::saving memanggil canonicalName() sehingga item baru
 *    (form, import, seeder) selalu memakai ejaan kategori yang sudah ada,
 *  - syncMissing() menambal kategori yang dipakai item tapi belum punya baris.
 *
 * AKSES: kategori tanpa viewer = terbuka untuk semua yang punya akses modul
 * Budgeting; dengan viewer = hanya mereka + Owner/Developer. Data budget
 * sensitif, jadi pembatasan ini dipakai di SEMUA jalur baca: halaman,
 * ringkasan, grafik, PDF, dan Export Excel (lihat BudgetReport).
 * ---------------------------------------------------------------------
 */
#[Fillable(['project_id', 'name', 'color', 'sort_order'])]
class BudgetCategory extends Model
{
    public static function normalize(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    public function effectiveColor(): string
    {
        return $this->color ?: ProjectSection::defaultColorFor($this->name);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function viewers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'budget_category_viewers', 'budget_category_id', 'user_id');
    }

    public function isRestricted(): bool
    {
        return $this->viewers->isNotEmpty();
    }

    public function isVisibleTo(User $user): bool
    {
        return $user->isOwnerOrDeveloper()
            || ! $this->isRestricted()
            || $this->viewers->contains('id', $user->id);
    }

    /**
     * Kunci "project|nama" semua kategori TERBATAS yang tidak boleh dilihat
     * $user. Null = tidak ada yang disembunyikan (Owner/Developer atau
     * memang tidak ada kategori terbatas). Dipakai menyaring koleksi item.
     *
     * @return array<string, true>
     */
    public static function hiddenKeysFor(User $user): array
    {
        if ($user->isOwnerOrDeveloper()) {
            return [];
        }

        $hidden = [];

        foreach (static::query()->has('viewers')->with('viewers:id')->get() as $category) {
            if (! $category->viewers->contains('id', $user->id)) {
                $hidden[static::key($category->project_id, $category->name)] = true;
            }
        }

        return $hidden;
    }

    public static function key(?int $projectId, ?string $name): string
    {
        return (int) $projectId . '|' . static::normalize($name);
    }

    /** Cari baris kategori (tanpa membedakan huruf besar/kecil). */
    public static function findByName(int $projectId, ?string $name): ?self
    {
        $needle = static::normalize($name);

        if ($needle === '') {
            return null;
        }

        return static::query()->where('project_id', $projectId)->with('viewers:id')->get()
            ->first(fn(self $c) => static::normalize($c->name) === $needle);
    }

    /**
     * Ejaan kategori yang dipakai di database: kalau sudah ada (beda huruf
     * besar/kecil pun) pakai ejaan itu, kalau belum buat baru di urutan
     * paling bawah. Idempotent.
     */
    public static function canonicalName(int $projectId, string $name): string
    {
        $name = mb_substr(trim($name), 0, 100);

        if ($name === '') {
            return $name;
        }

        if ($existing = static::findByName($projectId, $name)) {
            return $existing->name;
        }

        DB::table('budget_categories')->insertOrIgnore([
            'project_id' => $projectId,
            'name' => $name,
            'color' => null,
            'sort_order' => (int) static::query()->where('project_id', $projectId)->max('sort_order') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $name;
    }

    /** Buat baris untuk kategori yang dipakai item tapi belum terdaftar (urut menurut item pertama). */
    public static function syncMissing(): void
    {
        $known = static::query()->get(['project_id', 'name'])
            ->mapWithKeys(fn(self $c) => [static::key($c->project_id, $c->name) => true]);

        $next = static::query()->selectRaw('project_id, MAX(sort_order) as top')
            ->groupBy('project_id')->pluck('top', 'project_id')->map(fn($v) => (int) $v)->all();

        $rows = [];

        foreach (DB::table('project_budgets')->orderBy('id')->get(['project_id', 'category']) as $line) {
            $name = mb_substr(trim((string) $line->category), 0, 100);
            $key = static::key($line->project_id, $name);

            if ($name === '' || $known->has($key)) {
                continue;
            }

            $known->put($key, true);
            $next[$line->project_id] = ($next[$line->project_id] ?? 0) + 1;
            $rows[] = [
                'project_id' => $line->project_id,
                'name' => $name,
                'color' => null,
                'sort_order' => $next[$line->project_id],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('budget_categories')->insertOrIgnore($chunk);
        }
    }

    /** @return Collection<int, self> semua kategori 1 project, terurut, sort_order dirapikan 1..n. */
    public static function orderedFor(int $projectId): Collection
    {
        $rows = static::query()->where('project_id', $projectId)->orderBy('sort_order')->orderBy('id')->get();

        foreach ($rows as $i => $row) {
            if ($row->sort_order !== $i + 1) {
                $row->update(['sort_order' => $i + 1]);
            }
        }

        return $rows->values();
    }
}