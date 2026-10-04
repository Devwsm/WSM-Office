<?php

namespace App\Support;

use App\Models\BudgetCategory;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectBudgetPlan;
use App\Models\RoyaltyEntry;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Support\Collection;

/**
 * BudgetReport
 * ---------------------------------------------------------------------
 * Satu-satunya tempat angka "Budget vs Actual" dihitung. Dipakai halaman
 * Project Budgeting DAN PDF laporan, supaya angka di layar dan di kertas
 * tidak bisa berbeda. Padanan `budgetTotals()` + `budgetChartDataV22()`
 * di prototype v22.
 *
 * Pengelompokan grafik tidak membedakan huruf besar/kecil dan spasi
 * pinggir ("Marketing" = "marketing "), label yang tampil adalah ejaan
 * yang pertama ditemukan.
 *
 * 2026-10-04 — AKSES: `forProject()` menerima $viewer; item di kategori
 * terbatas (BudgetCategory::viewers) yang tidak boleh dilihat viewer
 * dibuang SEBELUM angka dihitung, jadi ringkasan, grafik, PDF, dan Excel
 * tidak membocorkan total kategori yang disembunyikan. Tanpa $viewer =
 * semua baris (dipakai test/skrip internal, bukan halaman).
 * ---------------------------------------------------------------------
 */
class BudgetReport
{
    /** Pilihan pengelompokan grafik: kunci => label tombol. */
    public const GROUPS = [
        'category' => 'Per Kategori',
        'project' => 'Per Project',
        'song' => 'Per Lagu',
    ];

    public const NO_SONG_LABEL = 'Tanpa lagu';

    /** Pilihan filter Status: kunci => label. */
    public const STATUSES = [
        'over' => 'Melebihi budget',
        'within' => 'Dalam budget',
        'unspent' => 'Belum ada realisasi',
    ];

    /** @param  Collection<int, ProjectBudget>  $lines  sudah di-load relasi `project` */
    public function __construct(private readonly Collection $lines) {}

    /**
     * @param  array{category?: ?string, song?: ?string, status?: ?string}  $filters
     */
    public static function forProject(?int $projectId, ?User $viewer = null, array $filters = []): self
    {
        $hidden = $viewer ? BudgetCategory::hiddenKeysFor($viewer) : [];
        $category = BudgetCategory::normalize($filters['category'] ?? null);
        $song = BudgetCategory::normalize($filters['song'] ?? null);
        $status = array_key_exists((string) ($filters['status'] ?? ''), self::STATUSES) ? $filters['status'] : null;

        $lines = ProjectBudget::query()
            ->with('project')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('category')
            ->orderBy('id')
            ->get()
            ->reject(fn(ProjectBudget $l) => isset($hidden[BudgetCategory::key($l->project_id, $l->category)]))
            ->when($category !== '', fn($c) => $c->filter(fn(ProjectBudget $l) => BudgetCategory::normalize($l->category) === $category))
            ->when($song !== '', fn($c) => $c->filter(fn(ProjectBudget $l) => BudgetCategory::normalize($l->song_title) === $song))
            ->when($status, fn($c) => $c->filter(fn(ProjectBudget $l) => match ($status) {
                'over' => $l->actual > $l->budget,
                'within' => $l->actual > 0 && $l->actual <= $l->budget,
                default => (float) $l->actual === 0.0,
            }))
            ->values();

        return new self($lines);
    }

    /**
     * Susunan 3 lapis untuk halaman: Project > Kategori > Item. Semua project
     * di $projects dapat kartu (juga yang belum punya item — supaya Project
     * Budget-nya bisa diisi). Kategori terdaftar tampil menurut urutan yang
     * diatur (yang kosong ikut tampil kalau tidak sedang difilter); kategori
     * terbatas yang tidak boleh dilihat $viewer tidak muncul sama sekali.
     *
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, array<string, mixed>>
     */
    public function cards(Collection $projects, User $viewer, bool $filtering): Collection
    {
        BudgetCategory::syncMissing();

        $plans = ProjectBudgetPlan::query()->whereIn('project_id', $projects->pluck('id'))->get()->keyBy('project_id');
        $categories = BudgetCategory::query()->with('viewers:id,name')
            ->whereIn('project_id', $projects->pluck('id'))
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('project_id');
        $byProject = $this->lines->groupBy('project_id');

        return $projects->map(function (Project $project) use ($plans, $categories, $byProject, $viewer, $filtering) {
            $lines = $byProject->get($project->id, collect());
            $registered = $categories->get($project->id, collect());
            $visible = $registered->filter(fn(BudgetCategory $c) => $c->isVisibleTo($viewer))->values();
            $groups = $lines->groupBy(fn(ProjectBudget $l) => BudgetCategory::normalize($l->category));

            $sections = $visible->map(function (BudgetCategory $category, int $index) use ($groups, $visible) {
                $rows = $groups->get(BudgetCategory::normalize($category->name), collect())->values();

                return [
                    'model' => $category,
                    'name' => $category->name,
                    'color' => $category->effectiveColor(),
                    'viewer_ids' => $category->viewers->pluck('id')->all(),
                    'rows' => $rows,
                    'is_first' => $index === 0,
                    'is_last' => $index === $visible->count() - 1,
                ] + self::summarize($rows);
            })->filter(fn(array $s) => $s['rows']->isNotEmpty() || ! $filtering)->values();

            $plan = $plans->get($project->id);

            return [
                'project' => $project,
                'plan' => $plan?->project_budget,
                'has_hidden' => $registered->count() > $visible->count(),
                'sections' => $sections,
                'item_count' => $lines->count(),
            ] + self::summarize($lines);
        })->values();
    }

    public static function normalizeGroup(?string $group): string
    {
        return array_key_exists((string) $group, self::GROUPS) ? $group : 'category';
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    /** Daftar kategori (nama unik tanpa beda huruf) dari baris yang tersedia — untuk dropdown filter. @return array<int,string> */
    public function categoryNames(): array
    {
        return self::uniqueCaseInsensitive($this->lines->pluck('category')->all());
    }

    /** Daftar lagu (nama unik tanpa beda huruf) dari baris yang tersedia — untuk dropdown filter. @return array<int,string> */
    public function songNames(): array
    {
        return self::uniqueCaseInsensitive($this->lines->pluck('song_title')->all());
    }

    /**
     * Total seluruh baris. `utilization` = persen realisasi terhadap budget
     * (dibulatkan), null kalau belum ada budget sama sekali (tidak bisa
     * dihitung — bukan 0%).
     *
     * @return array{budget: float, actual: float, remaining: float, utilization: int|null}
     */
    public function totals(): array
    {
        return self::summarize($this->lines);
    }

    /**
     * Baris dikelompokkan per project (urut nama), lengkap subtotalnya.
     *
     * @return Collection<int, array{project: string, rows: Collection<int, ProjectBudget>, budget: float, actual: float, remaining: float, utilization: int|null}>
     */
    public function byProject(): Collection
    {
        return $this->lines
            ->groupBy('project_id')
            ->map(fn(Collection $rows) => ['project' => (string) ($rows->first()->project?->name ?? '-'), 'rows' => $rows->values()] + self::summarize($rows))
            ->sortBy(fn(array $g) => mb_strtolower($g['project']))
            ->values();
    }

    /**
     * Data batang grafik untuk 1 mode pengelompokan. Setiap batang:
     * label, budget, actual, remaining, utilization, over (actual > budget),
     * plan_pct/actual_pct (lebar batang relatif ke nilai terbesar, 0-100).
     * Diurutkan budget terbesar dulu (seperti prototype); "Tanpa lagu"
     * selalu paling bawah.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function chart(string $group): Collection
    {
        $group = self::normalizeGroup($group);

        $rows = $this->lines
            ->groupBy(fn(ProjectBudget $line) => mb_strtolower($this->labelFor($line, $group)))
            ->map(fn(Collection $rows) => ['label' => $this->labelFor($rows->first(), $group)] + self::summarize($rows))
            ->sort(function (array $a, array $b) {
                $aNone = $a['label'] === self::NO_SONG_LABEL;
                $bNone = $b['label'] === self::NO_SONG_LABEL;

                return $aNone <=> $bNone ?: ($b['budget'] <=> $a['budget']) ?: strcasecmp($a['label'], $b['label']);
            })
            ->values();

        $max = (float) $rows->map(fn(array $r) => max($r['budget'], $r['actual']))->max();

        return $rows->map(fn(array $r) => $r + [
            'over' => $r['actual'] > $r['budget'],
            'plan_pct' => $max > 0 ? round($r['budget'] / $max * 100, 1) : 0.0,
            'actual_pct' => $max > 0 ? round($r['actual'] / $max * 100, 1) : 0.0,
        ]);
    }

    /**
     * Saran isian Kategori (kolom "kategori baru" di form): yang sudah dipakai
     * tim di budget lebih dulu (hanya dari kategori yang boleh dilihat $viewer),
     * lalu section standar Work Tracker. Duplikat beda huruf dibuang.
     *
     * @return array<int, string>
     */
    public static function categorySuggestions(?User $viewer = null): array
    {
        $used = self::forProject(null, $viewer)->categoryNames();

        return self::uniqueCaseInsensitive([...$used, ...WorkItem::SECTION_SUGGESTIONS]);
    }

    /**
     * Saran isian Lagu di form: judul yang sudah dipakai di budget (hanya dari
     * kategori yang boleh dilihat $viewer) ditambah judul entri Royalty.
     *
     * @return array<int, string>
     */
    public static function songSuggestions(?User $viewer = null): array
    {
        $used = self::forProject(null, $viewer)->songNames();
        $royalty = RoyaltyEntry::query()->distinct()->orderBy('title')->pluck('title')->all();

        return self::uniqueCaseInsensitive([...$used, ...$royalty]);
    }

    private function labelFor(ProjectBudget $line, string $group): string
    {
        $label = match ($group) {
            'project' => (string) ($line->project?->name ?? '-'),
            'song' => (string) $line->song_title,
            default => (string) $line->category,
        };

        $label = trim($label);

        return $label !== '' ? $label : ($group === 'song' ? self::NO_SONG_LABEL : '-');
    }

    /** @return array{budget: float, actual: float, remaining: float, utilization: int|null} */
    private static function summarize(Collection $rows): array
    {
        $budget = (float) $rows->sum('budget');
        $actual = (float) $rows->sum('actual');

        return [
            'budget' => $budget,
            'actual' => $actual,
            'remaining' => $budget - $actual,
            'utilization' => $budget > 0 ? (int) round($actual / $budget * 100) : null,
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private static function uniqueCaseInsensitive(array $values): array
    {
        $seen = [];

        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                $seen[mb_strtolower($value)] ??= $value;
            }
        }

        return array_values($seen);
    }
}