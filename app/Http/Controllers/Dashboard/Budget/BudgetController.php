<?php

namespace App\Http\Controllers\Dashboard\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Budget\BudgetRequest;
use App\Models\AuditLog;
use App\Support\Audit;
use App\Models\BudgetCategory;
use App\Models\BudgetFund;
use App\Models\DashboardAccess;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Support\BudgetReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * BudgetController (Dashboard > Project Budgeting)
 * ---------------------------------------------------------------------
 * Fase 13 — CRUD baris budget-vs-actual per project, padanan
 * `saveBudgetEntry()` di prototype v18.
 *
 * 2026-10-04 — disamakan konsepnya dengan Work Tracker (keluhan tim: UI
 * beda dari prototype):
 *  - 3 lapis: Project > Kategori (padanan section) > Item, accordion,
 *  - filter Project / Kategori / Lagu / Status + Expand/Collapse All,
 *  - edit satuan langsung di tabel item (nama, lagu, budget, actual,
 *    bukti, catatan) lewat PATCH JSON `updateField()`,
 *  - kategori bisa diatur: warna, urutan, tambah, hapus (kosong saja),
 *  - akses per orang di level kategori (halaman sendiri),
 *  - "Project Budget" = dana KESELURUHAN semua project (satu angka, hanya
 *    diedit, bisa diisi walau belum ada project) vs "Budget Allocation"
 *    (jumlah budget semua item; dulu berlabel "Total Budget"). Selisihnya
 *    = Unallocated, atau Over-allocated kalau item melebihi dana.
 *
 * DATA SENSITIF: Tambah/Edit item, edit Project Budget, dan atur akses
 * kategori semuanya halaman sendiri (BUKAN modal). Kategori terbatas
 * disembunyikan dari semua jalur baca (halaman, grafik, PDF, Excel) dan
 * semua jalur tulis (edit/hapus/edit satuan/form) lewat
 * `authorizeLine()` / `authorizeCategory()`.
 *
 * Ringkasan, grafik, dan PDF dihitung oleh App\Support\BudgetReport —
 * satu sumber angka untuk layar dan kertas.
 *
 * Gate 'view'/'manage' modul 'budget' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class BudgetController extends Controller
{
    /** Kunci filter yang dibawa ke link grafik, PDF, dan Export. */
    private const FILTER_KEYS = ['project_id', 'category', 'song', 'status'];

    public function index(Request $request)
    {
        $viewer = $request->user();
        $selectedProjectId = $request->integer('project_id') ?: null;
        $group = BudgetReport::normalizeGroup($request->query('group'));
        $filters = $this->filters($request);
        $lineFiltering = (bool) ($filters['category'] || $filters['song'] || $filters['status']);

        $report = BudgetReport::forProject($selectedProjectId, $viewer, $filters);
        // Pilihan dropdown filter diambil dari SEMUA baris yang boleh dilihat,
        // bukan dari yang lolos filter, supaya pilihan tidak menyusut saat difilter.
        $options = BudgetReport::forProject(null, $viewer);

        $projects = $this->projects();
        $scoped = $projects->when($selectedProjectId, fn($c) => $c->where('id', $selectedProjectId))->values();

        $cards = $report->cards($scoped, $viewer, $lineFiltering)
            ->when($lineFiltering, fn($c) => $c->filter(fn(array $card) => $card['item_count'] > 0))
            ->values();

        // Dana keseluruhan vs total alokasi SEMUA item. Selisihnya hanya ditampilkan kalau peminta
        // melihat semua kategori: kalau ada yang disembunyikan, selisih akan membocorkan total
        // kategori itu (atau menyesatkan kalau dihitung dari yang terlihat saja).
        $fund = BudgetFund::amount();
        $allocationAll = (float) ProjectBudget::query()->sum('budget');
        $unallocated = ($fund !== null && BudgetCategory::hiddenKeysFor($viewer) === []) ? $fund - $allocationAll : null;

        return view('dashboard.budget.index', [
            'report' => $report,
            'totals' => $report->totals(),
            'chart' => $report->chart($group),
            'group' => $group,
            'groups' => BudgetReport::GROUPS,
            'cards' => $cards,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'selectedCategory' => $filters['category'],
            'selectedSong' => $filters['song'],
            'selectedStatus' => $filters['status'],
            'categoryOptions' => $options->categoryNames(),
            'songOptions' => $options->songNames(),
            'statusOptions' => BudgetReport::STATUSES,
            'filtering' => (bool) ($selectedProjectId || $lineFiltering),
            'lineFiltering' => $lineFiltering,
            'fund' => $fund,
            'unallocated' => $unallocated,
            'scope' => array_filter(array_intersect_key($filters + ['project_id' => $selectedProjectId], array_flip(self::FILTER_KEYS))),
        ]);
    }

    /**
     * Laporan cetak (A4 landscape): ringkasan, grafik, lalu tabel per project.
     * Dibuka inline supaya langsung bisa dicetak atau disimpan dari viewer PDF.
     * Mengikuti filter & pengelompokan grafik yang sedang dibuka, dan hanya
     * memuat kategori yang boleh dilihat peminta.
     */
    public function pdf(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;
        $group = BudgetReport::normalizeGroup($request->query('group'));
        $project = $selectedProjectId ? Project::query()->find($selectedProjectId) : null;

        $report = BudgetReport::forProject($selectedProjectId, $request->user(), $this->filters($request));

        $filename = Str::slug('laporan-anggaran-' . ($project?->name ?? 'semua-project') . '-' . now()->format('Y-m-d')) . '.pdf';

        return Pdf::loadView('pdf.budget-report', [
            'totals' => $report->totals(),
            'chart' => $report->chart($group),
            'groupLabel' => BudgetReport::GROUPS[$group],
            'entriesByProject' => $report->byProject(),
            'scopeLabel' => $project?->name ?? 'Semua project',
            // Dana keseluruhan hanya relevan untuk laporan tanpa filter (cakupan = semua project).
            'projectBudget' => array_filter($this->filters($request)) === [] ? BudgetFund::amount() : null,
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    // --- Item: Tambah / Edit (halaman sendiri, bukan modal) ---

    public function create(Request $request)
    {
        return view('dashboard.budget.create', [
            'projects' => $this->projects(),
            'prefill' => [
                'project_id' => $request->integer('project_id') ?: null,
                'category' => (string) $request->query('category', ''),
            ],
        ] + $this->formData($request->user()));
    }

    public function store(BudgetRequest $request)
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $line = ProjectBudget::create($data);

        AuditLog::record('Budget item ditambahkan', 'Budget ' . Audit::summary($line, Audit::labels('budget')) . '.', $request->user());

        return redirect()->route('dashboard.budget.index')->with('status', 'Baris budget berhasil ditambahkan.');
    }

    public function edit(Request $request, ProjectBudget $budget)
    {
        $this->authorizeLine($request, $budget);

        return view('dashboard.budget.edit', [
            'budget' => $budget,
            'projects' => $this->projects(),
            'prefill' => ['project_id' => $budget->project_id, 'category' => $budget->category],
        ] + $this->formData($request->user()));
    }

    public function update(BudgetRequest $request, ProjectBudget $budget)
    {
        $this->authorizeLine($request, $budget);

        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $changes = Audit::changes($budget, $data, Audit::labels('budget'));
        $budget->update($data);

        AuditLog::record('Budget item diperbarui', "Budget \"{$budget->item}\": {$changes}.", $request->user());

        return redirect()->route('dashboard.budget.index')->with('status', 'Baris budget berhasil diperbarui.');
    }

    public function destroy(Request $request, ProjectBudget $budget)
    {
        $this->authorizeLine($request, $budget);

        $summary = Audit::summary($budget, Audit::labels('budget'));
        $budget->delete();

        AuditLog::record('Budget item dihapus', "Budget dihapus: {$summary}.", $request->user());

        return back()->with('status', 'Baris budget berhasil dihapus.');
    }

    /**
     * Edit satuan 1 kolom langsung dari tabel item (padanan
     * WorkTrackerBoardController::updateField). Hanya kolom yang dikirim
     * yang diubah; kategori & project diubah lewat halaman Edit.
     */
    public function updateField(Request $request, ProjectBudget $budget)
    {
        $this->authorizeLine($request, $budget);

        $field = $request->validate([
            'field' => ['required', Rule::in(['item', 'song_title', 'budget', 'actual', 'proof_link', 'note'])],
        ])['field'];

        $raw = $request->input('value');
        $raw = is_string($raw) ? trim($raw) : $raw;

        // Link tanpa skema dilengkapi https:// (sama dengan BudgetRequest); skema lain ditolak.
        if ($field === 'proof_link' && is_string($raw) && $raw !== '' && ! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $raw)) {
            $raw = 'https://' . ltrim($raw, '/');
        }

        $rules = [
            'item' => ['required', 'string', 'max:150'],
            'song_title' => ['nullable', 'string', 'max:150'],
            'budget' => ['required', 'numeric', 'min:0'],
            'actual' => ['nullable', 'numeric', 'min:0'],
            'proof_link' => ['nullable', 'url:http,https', 'max:500'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];

        $value = validator(['value' => $raw], ['value' => $rules[$field]], [], ['value' => 'Isian'])->validate()['value'] ?? null;
        $value = ($value === '' || $value === null) ? null : $value;

        // Kolom angka tidak boleh null di database; actual kosong = 0.
        if ($field === 'actual') {
            $value ??= 0;
        }

        $changes = Audit::changes($budget, [$field => $value], Audit::labels('budget'));
        $budget->update([$field => $value, 'updated_by' => $request->user()->id]);

        AuditLog::record('Budget item diubah (edit cepat)', "Budget \"{$budget->item}\": {$changes}.", $request->user());

        return response()->json(['ok' => true, 'value' => $budget->fresh()->{$field}]);
    }

    // --- Project Budget (dana keseluruhan) — hanya diedit, halaman sendiri ---

    public function editFund()
    {
        return view('dashboard.budget.fund-edit', [
            'fund' => BudgetFund::amount(),
            'allocation' => (float) ProjectBudget::query()->sum('budget'),
        ]);
    }

    public function updateFund(Request $request)
    {
        $data = $request->validate(
            ['project_budget' => ['required', 'numeric', 'min:0', 'max:99999999999999']],
            [],
            ['project_budget' => 'Project Budget']
        );

        $previous = BudgetFund::amount();

        BudgetFund::set((float) $data['project_budget'], $request->user()->id);

        AuditLog::record(
            'Project Budget diubah',
            'Dana Project Budget ' . ($previous === null ? 'diisi' : 'diubah dari ' . number_format($previous, 0, ',', '.')) . ' menjadi ' . number_format((float) $data['project_budget'], 0, ',', '.') . ' oleh ' . $request->user()->name . '.',
            $request->user()
        );

        return redirect()->route('dashboard.budget.index')->with('status', 'Project Budget disimpan.');
    }

    // --- Kategori: tambah, warna, urutan, hapus, akses ---

    public function storeCategory(Request $request, Project $project)
    {
        $name = trim($request->validate(['name' => ['required', 'string', 'max:100']])['name']);

        if (BudgetCategory::findByName($project->id, $name)) {
            return back()->withErrors(['name' => 'Kategori "' . $name . '" sudah ada di project ini.']);
        }

        BudgetCategory::canonicalName($project->id, $name);

        AuditLog::record('Kategori budget ditambahkan', "Kategori \"{$name}\" ditambahkan di project \"{$project->name}\".", $request->user());

        return back()->with('status', 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $request, BudgetCategory $category)
    {
        $this->authorizeCategory($request, $category);

        $data = $request->validate(['color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']]);
        $oldColor = $category->color ?? 'bawaan';
        $category->update(['color' => strtolower($data['color'])]);

        AuditLog::record('Warna kategori budget diubah', "Kategori \"{$category->name}\" (project #{$category->project_id}): warna {$oldColor} → {$category->color}.", $request->user());

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('status', 'Warna kategori diperbarui.');
    }

    /** Geser 1 posisi di antara kategori yang BISA DILIHAT peminta (yang tersembunyi dilewati). */
    public function moveCategory(Request $request, BudgetCategory $category)
    {
        $this->authorizeCategory($request, $category);

        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $viewer = $request->user();

        $ordered = BudgetCategory::orderedFor($category->project_id);
        $ordered->load('viewers:id');
        $visible = $ordered->filter(fn(BudgetCategory $c) => $c->isVisibleTo($viewer))->values();
        $index = $visible->search(fn(BudgetCategory $c) => $c->id === $category->id);
        $neighbor = $index === false ? null : $visible->get($data['direction'] === 'up' ? $index - 1 : $index + 1);

        if ($neighbor !== null && ! ($data['direction'] === 'up' && $index === 0)) {
            [$a, $b] = [$visible[$index]->sort_order, $neighbor->sort_order];
            $visible[$index]->update(['sort_order' => $b]);
            $neighbor->update(['sort_order' => $a]);

            AuditLog::record('Urutan kategori budget diubah', "Kategori \"{$category->name}\" (project #{$category->project_id}) digeser " . ($data['direction'] === 'up' ? 'ke atas' : 'ke bawah') . '.', $viewer);
        }

        return back();
    }

    /**
     * Hapus kategori — HANYA kalau sudah kosong. Beda dari section Work
     * Tracker (yang ikut menghapus item): ini data keuangan, jadi item
     * harus dihapus/dipindah satu per satu dulu, tidak bisa terhapus massal
     * karena salah klik.
     */
    public function destroyCategory(Request $request, BudgetCategory $category)
    {
        $this->authorizeCategory($request, $category);

        $count = ProjectBudget::query()->where('project_id', $category->project_id)->get(['category'])
            ->filter(fn($l) => BudgetCategory::normalize($l->category) === BudgetCategory::normalize($category->name))
            ->count();

        if ($count > 0) {
            return back()->withErrors(['category' => 'Kategori "' . $category->name . '" masih berisi ' . $count . ' item. Hapus atau pindahkan item-nya dulu.']);
        }

        $category->delete();

        AuditLog::record('Kategori budget dihapus', "Kategori \"{$category->name}\" (project #{$category->project_id}) dihapus.", $request->user());

        return back()->with('status', 'Kategori "' . $category->name . '" dihapus.');
    }

    /** Halaman atur siapa yang boleh melihat 1 kategori (halaman sendiri, bukan modal). */
    public function accessCategory(Request $request, BudgetCategory $category)
    {
        $this->authorizeCategory($request, $category);

        $category->load('project:id,name', 'viewers:id,name');

        return view('dashboard.budget.access', [
            'category' => $category,
            'people' => $this->eligibleViewers($category),
            'currentIds' => $category->viewers->pluck('id')->all(),
        ]);
    }

    /** Daftar kosong = terbuka untuk semua yang punya akses modul Budgeting. */
    public function updateCategoryViewers(Request $request, BudgetCategory $category)
    {
        $this->authorizeCategory($request, $category);

        $data = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $ids = array_map('intval', $data['user_ids'] ?? []);
        $category->viewers()->sync($ids);

        AuditLog::record(
            'Akses kategori budget diubah',
            'Kategori "' . $category->name . '" (project #' . $category->project_id . ') ' . ($ids === [] ? 'dibuka untuk semua' : 'dibatasi untuk ' . count($ids) . ' orang') . ' oleh ' . $request->user()->name . '.',
            $request->user()
        );

        return redirect()->route('dashboard.budget.index', ['project_id' => $category->project_id])
            ->with('status', $ids === [] ? 'Kategori terbuka untuk semua.' : 'Akses kategori disimpan.');
    }

    // --- helper ---

    /** @return array{project_id: ?int, category: ?string, song: ?string, status: ?string} */
    private function filters(Request $request): array
    {
        return [
            'project_id' => $request->integer('project_id') ?: null,
            'category' => trim((string) $request->query('category', '')) ?: null,
            'song' => trim((string) $request->query('song', '')) ?: null,
            'status' => array_key_exists((string) $request->query('status'), BudgetReport::STATUSES) ? $request->query('status') : null,
        ];
    }

    /** Item di kategori yang tidak boleh dilihat peminta = 404 (tidak membocorkan keberadaannya). */
    private function authorizeLine(Request $request, ProjectBudget $budget): void
    {
        abort_unless($budget->isVisibleTo($request->user()), 404);
    }

    private function authorizeCategory(Request $request, BudgetCategory $category): void
    {
        $category->loadMissing('viewers:id');

        abort_unless($category->isVisibleTo($request->user()), 404);
    }

    /**
     * Orang yang masuk akal dipilih sebagai viewer: punya akses modul Budgeting
     * (view/manage). Owner/Developer selalu melihat semuanya jadi tidak perlu
     * dipilih. Viewer yang sudah terpilih tetap ditampilkan walau aksesnya dicabut.
     */
    private function eligibleViewers(BudgetCategory $category)
    {
        $withAccess = DashboardAccess::query()->where('module', 'budget')->where('level', '!=', 'none')->pluck('user_id');

        return User::query()
            ->whereNotIn('role', ['owner', 'developer'])
            ->where(fn($q) => $q->whereIn('id', $withAccess)->orWhereIn('id', $category->viewers->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    /** Data form Tambah/Edit: kategori yang sudah ada per project (hanya yang boleh dilihat). */
    private function formData(User $viewer): array
    {
        BudgetCategory::syncMissing();

        $byProject = BudgetCategory::query()->with('viewers:id')->orderBy('sort_order')->orderBy('id')->get()
            ->filter(fn(BudgetCategory $c) => $c->isVisibleTo($viewer))
            ->groupBy('project_id')
            ->map(fn($rows) => $rows->pluck('name')->values()->all())
            ->all();

        return [
            'categoriesByProject' => $byProject,
            'categorySuggestions' => BudgetReport::categorySuggestions($viewer),
            'songSuggestions' => BudgetReport::songSuggestions($viewer),
        ];
    }

    private function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name', 'color']);
    }
}