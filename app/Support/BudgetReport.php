<?php

namespace App\Support;

use App\Models\ProjectBudget;
use App\Models\RoyaltyEntry;
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

    /** @param  Collection<int, ProjectBudget>  $lines  sudah di-load relasi `project` */
    public function __construct(private readonly Collection $lines) {}

    public static function forProject(?int $projectId): self
    {
        $lines = ProjectBudget::query()
            ->with('project')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('category')
            ->orderBy('id')
            ->get();

        return new self($lines);
    }

    public static function normalizeGroup(?string $group): string
    {
        return array_key_exists((string) $group, self::GROUPS) ? $group : 'category';
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
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
     * Saran isian Kategori di form: yang sudah dipakai tim di budget lebih
     * dulu (kosakata mereka sendiri), lalu section standar Work Tracker.
     * Duplikat beda huruf dibuang.
     *
     * @return array<int, string>
     */
    public static function categorySuggestions(): array
    {
        $used = ProjectBudget::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all();

        return self::uniqueCaseInsensitive([...$used, ...WorkItem::SECTION_SUGGESTIONS]);
    }

    /**
     * Saran isian Lagu di form: judul yang sudah dipakai di budget ditambah
     * judul entri Royalty (judul rilis/lagu yang dilaporkan).
     *
     * @return array<int, string>
     */
    public static function songSuggestions(): array
    {
        $used = ProjectBudget::query()->whereNotNull('song_title')->where('song_title', '!=', '')->distinct()->orderBy('song_title')->pluck('song_title')->all();
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