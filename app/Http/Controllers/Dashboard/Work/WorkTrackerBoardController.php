<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\ProjectRequest;
use App\Http\Requests\Dashboard\Work\WorkItemRequest;
use App\Models\Project;
use App\Models\ProjectSection;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * WorkTrackerBoardController (Dashboard > Work Control)
 * ---------------------------------------------------------------------
 * Fase 9 lanjutan (audit ronde 6, 2026-09-09) — sisi ADMIN Work
 * Tracker: board kanban per `WorkItem::PROGRESS_OPTIONS`, CRUD Project,
 * assign PIC, drag-drop update progress. Padanan `renderAdminWorkBoard`
 * / `renderProjectManager` di prototype v18-v21 (fungsi ini gak pernah
 * ke-audit detail sebelumnya karena fokus audit ronde 1-5 selalu App
 * Mode karyawan — board ini KEBALIKANNYA, khusus yang punya
 * `module:work,manage`, BUKAN tampil di App Mode Home).
 *
 * ⚠️ SENGAJA dinamain "...BoardController", BUKAN "WorkTrackerController"
 * polos — nama itu udah dipakai `App\Http\Controllers\Employee\
 * WorkTrackerController` (Shared Calendar App Mode karyawan, dari Fase 9
 * awal). Nabrak nama bikin `use` statement di routes/web.php konflik
 * (Intelephense P1004 "Duplicate symbol declaration") — sudah pernah
 * kejadian di sesi ini, makanya nama akhirnya dipisah biar gak keulang.
 *
 * Beda dari "My Work Tracker" (kartu Home App Mode, sudah ada dari
 * Fase 9 awal): itu read-only, punya sendiri (`pic_employee_id` = diri
 * sendiri). Board ini CRUD penuh, semua task lintas PIC — makanya
 * digerbang `module:work` (dashboard_access), bukan otomatis semua
 * karyawan kayak kartu Home.
 *
 * Kolom board = `WorkItem::PROGRESS_OPTIONS` (Pending/On
 * Development/Follow Up/Confirmed/Done/Postpone) — SENGAJA bukan
 * `focus` (HARI INI/BESOK/dst), karena `focus` itu turunan due_date
 * (lihat `computedFocus()`), bukan status kerja yang bisa di-drag
 * manual. Prototype juga begitu: kanban-nya progress, badge focus cuma
 * pemanis di tiap card.
 * ---------------------------------------------------------------------
 */
class WorkTrackerBoardController extends Controller
{
    /**
     * Work Tracker = task PER PROJECT (2026-09-28): kartu project ->
     * accordion section -> tabel item, meniru `trackerBoardMarkup()`
     * prototype v31. Menggantikan board kanban lama (keputusan Fase 9)
     * karena tim komplain cara list project & isinya beda dari prototype.
     */
    public function index(Request $request)
    {
        $projects = Project::query()->orderBy('name')->get();
        $employees = User::query()->orderBy('name')->get(['id', 'name']);

        // Tambal baris warna/urutan untuk section yang belum terdaftar, lalu
        // baca sekali: [project_id => [nama => ProjectSection]] terurut.
        ProjectSection::syncMissing();
        $meta = ProjectSection::query()->orderBy('sort_order')->orderBy('id')->get()
            ->groupBy('project_id')
            ->map(fn($rows) => $rows->keyBy('name'));

        $selectedProjectId = $request->integer('project_id') ?: null;
        $selectedPic = $request->integer('pic') ?: null;
        $selectedProgress = in_array($request->query('progress'), WorkItem::PROGRESS_OPTIONS, true)
            ? $request->query('progress')
            : null;

        // Filter PIC/Progress menyembunyikan item — section kosong ikut
        // disembunyikan & tombol geser dimatikan (urutan penuh jadi tidak terlihat).
        $filtering = (bool) ($selectedPic || $selectedProgress);

        $all = WorkItem::query()->with(['pic', 'additionalPics'])->orderBy('due_date')->orderBy('item_no')->get();

        $stats = [
            'today' => $all->filter(fn(WorkItem $i) => $i->computedFocus() === 'HARI INI')->count(),
            'overdue' => $all->filter(fn(WorkItem $i) => $i->isOverdue())->count(),
            'follow_up' => $all->where('progress', 'Follow Up')->count(),
            'done' => $all->where('progress', 'Done')->count(),
        ];

        $totals = $all->groupBy(fn(WorkItem $i) => $i->project_id ?? 0);

        // Section yang sudah ada per project (key 0 = tanpa project) — dipakai
        // dropdown Section di form Tambah/Edit Task. Dari SEMUA item, bukan
        // yang lolos filter, biar section tidak hilang dari form saat difilter.
        // Section terdaftar (urutan yang diatur, termasuk yang masih kosong)
        // lebih dulu, lalu section yang dipakai item tapi belum terdaftar.
        $sectionsByProject = $totals->map(
            fn($rows, $projectId) => collect($meta->get($projectId, collect())->keys())
                ->merge($rows->sortBy('id')->pluck('section')->filter(fn($s) => $s !== ''))
                ->unique()->values()->all()
        )->all();
        foreach ($meta as $projectId => $rows) {
            $sectionsByProject[$projectId] ??= $rows->keys()->values()->all();
        }

        $visible = $all
            ->when($selectedProjectId, fn($c) => $c->where('project_id', $selectedProjectId))
            ->when($selectedPic, fn($c) => $c->filter(fn(WorkItem $i) => $i->hasPic($selectedPic)))
            ->when($selectedProgress, fn($c) => $c->where('progress', $selectedProgress))
            ->groupBy(fn(WorkItem $i) => $i->project_id ?? 0);

        $cards = $projects
            ->when($selectedProjectId, fn($c) => $c->where('id', $selectedProjectId))
            ->map(fn(Project $p) => $this->buildCard($p, $totals->get($p->id, collect()), $visible->get($p->id, collect()), $meta->get($p->id, collect()), $filtering))
            ->values();

        // Task tanpa project — kartu sendiri, cuma muncul kalau ada isinya.
        if (! $selectedProjectId && $visible->has(0)) {
            $cards->push($this->buildCard(null, $totals->get(0, collect()), $visible->get(0), collect(), $filtering));
        }

        return view('dashboard.work.tracker.index', [
            'projects' => $projects,
            'cards' => $cards,
            'stats' => $stats,
            'employees' => $employees,
            'sectionsByProject' => $sectionsByProject,
            'selectedProjectId' => $selectedProjectId,
            'selectedPic' => $selectedPic,
            'selectedProgress' => $selectedProgress,
            'filtering' => $filtering,
        ]);
    }

    /**
     * Halaman menu "Projects" (sidebar Work Control) — daftar project +
     * tombol "Add New Project" yang buka MODAL (bukan split screen).
     */
    public function projects()
    {
        $projects = Project::query()
            ->with('lead:id,name')
            ->withCount([
                'workItems as items_total',
                'workItems as items_done' => fn($q) => $q->where('progress', 'Done'),
            ])
            ->orderBy('name')
            ->get();

        return view('dashboard.work.projects.index', [
            'projects' => $projects,
            'employees' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Susun 1 kartu project: progres (dari SEMUA item) + section.
     *
     * Urutan section: yang terdaftar di `project_sections` menurut urutan
     * yang diatur (section kosong ikut tampil kalau tidak sedang difilter),
     * lalu section yang dipakai item tapi belum terdaftar, terakhir item
     * tanpa section. `$meta` = [nama => ProjectSection] milik project ini.
     */
    private function buildCard(?Project $project, $allItems, $visibleItems, $meta, bool $filtering): array
    {
        $total = $allItems->count();
        $done = $allItems->where('progress', 'Done')->count();

        $groups = $visibleItems->groupBy(fn(WorkItem $i) => $i->section);
        $sections = collect();

        $lastManaged = $meta->count() - 1;

        foreach ($meta->values() as $index => $section) {
            $name = $section->name;
            $rows = $groups->get($name, collect());

            if ($rows->isNotEmpty() || ! $filtering) {
                $sections->push([
                    'name' => (string) $name,
                    'rows' => $rows,
                    'model' => $section,
                    'color' => $section->effectiveColor(),
                    'is_first' => $index === 0,
                    'is_last' => $index === $lastManaged,
                ]);
            }
        }

        $groups->reject(fn($rows, $name) => $name === '' || $meta->has($name))
            ->map(fn($rows, $name) => ['name' => (string) $name, 'rows' => $rows, 'model' => null, 'color' => ProjectSection::defaultColorFor((string) $name), 'first' => $rows->min('id')])
            ->sortBy('first')
            ->each(fn($group) => $sections->push($group));

        if ($groups->has('')) {
            $sections->push(['name' => 'TANPA SECTION', 'rows' => $groups->get(''), 'model' => null, 'color' => ProjectSection::defaultColorFor('TANPA SECTION')]);
        }

        return [
            'project' => $project,
            'total' => $total,
            'done' => $done,
            'pct' => $total ? (int) round($done / $total * 100) : 0,
            'shown' => $visibleItems->count(),
            'sections' => $sections->values(),
        ];
    }

    // --- Project CRUD (modal "Kelola Projects" di board) ---

    public function storeProject(ProjectRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Project::uniqueSlugFrom($data['name']);
        // 2026-09-16 — sama alasan kenapa `slug` diisi eksplisit di sini
        // (bukan cuma andelin Project::booted()): hook `creating` TERNYATA
        // gak selalu keisi ke query INSERT (lihat ⚠️ di Project model),
        // jadi warna default juga diisi eksplisit di sini, bukan cuma
        // ngandelin hook.
        $data['color'] = $data['color'] ?: Project::nextPaletteColor();
        $data['created_by'] = $request->user()->id;

        Project::create($data);

        return back()->with('status', 'Project ditambahkan.');
    }

    public function updateProject(ProjectRequest $request, Project $project)
    {
        $project->update($request->validated());

        return back()->with('status', 'Project diperbarui.');
    }

    public function destroyProject(Project $project)
    {
        // WorkItem.project_id nullable (lihat migration) — hapus
        // project TIDAK ikut hapus task-nya, cuma lepas ikatan,
        // konsisten sama cara Meeting/ProjectBudget nunjuk ke Project.
        $project->workItems()->update(['project_id' => null]);
        $project->delete();

        return back()->with('status', 'Project dihapus. Task yang nempel dipindah jadi "Tanpa Project".');
    }

    // --- Section CRUD (warna, urutan, tambah, hapus — per project) ---

    public function storeSection(Request $request, Project $project)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $name = trim($data['name']);

        // Unik per project, tanpa membedakan huruf besar/kecil ("Contract" = "CONTRACT").
        $exists = ProjectSection::query()->where('project_id', $project->id)
            ->get(['name'])->contains(fn($s) => mb_strtolower($s->name) === mb_strtolower($name));

        if ($name === '' || $exists) {
            return back()->withErrors(['name' => 'Section "' . $name . '" sudah ada di project ini.']);
        }

        ProjectSection::ensure($project->id, $name);

        return back()->with('status', 'Section ditambahkan.');
    }

    public function updateSection(Request $request, ProjectSection $section)
    {
        $data = $request->validate(['color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']]);

        $section->update(['color' => strtolower($data['color'])]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Warna section diperbarui.');
    }

    /** Geser 1 posisi ke atas/bawah dalam urutan section project itu. */
    public function moveSection(Request $request, ProjectSection $section)
    {
        $data = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);

        DB::transaction(function () use ($section, $data) {
            $ordered = ProjectSection::orderedFor($section->project_id);
            $index = $ordered->search(fn($s) => $s->id === $section->id);

            if ($index === false) {
                return;
            }

            $neighbor = $ordered->get($data['direction'] === 'up' ? $index - 1 : $index + 1);

            // Sudah di ujung: tidak ada yang digeser.
            if ($neighbor === null || ($data['direction'] === 'up' && $index === 0)) {
                return;
            }

            $current = $ordered[$index];
            [$a, $b] = [$current->sort_order, $neighbor->sort_order];
            $current->update(['sort_order' => $b]);
            $neighbor->update(['sort_order' => $a]);
        });

        return back();
    }

    /**
     * Hapus section (padanan `deleteProjectSectionV26`): section KOSONG
     * langsung terhapus; kalau berisi item, item-itemnya IKUT terhapus dan
     * nama section harus diketik persis sebagai konfirmasi.
     */
    public function destroySection(Request $request, ProjectSection $section)
    {
        $items = WorkItem::query()->where('project_id', $section->project_id)->where('section', $section->name);
        $count = (clone $items)->count();

        if ($count > 0) {
            $request->validate(
                ['confirm_name' => ['required', 'string', Rule::in([$section->name])]],
                ['confirm_name.in' => 'Nama section tidak cocok. Penghapusan dibatalkan.', 'confirm_name.required' => 'Ketik nama section untuk konfirmasi.']
            );
        }

        DB::transaction(function () use ($items, $section) {
            $items->delete();
            $section->delete();
        });

        return back()->with('status', $count > 0
            ? 'Section "' . $section->name . '" dan ' . $count . ' item di dalamnya dihapus.'
            : 'Section "' . $section->name . '" dihapus.');
    }

    // --- WorkItem CRUD ---

    public function storeItem(WorkItemRequest $request)
    {
        $data = $request->validated();
        $extraPicIds = $this->pullAdditionalPicIds($data);
        $data['created_by'] = $request->user()->id;
        $data['item_no'] = $this->nextItemNo($data['project_id'] ?? null, $data['section'] ?? null);

        $item = WorkItem::create($data);
        $this->syncAdditionalPics($item, $extraPicIds);

        return back()->with('status', 'Task ditambahkan.');
    }

    public function updateItem(WorkItemRequest $request, WorkItem $item)
    {
        $data = $request->validated();
        $extraPicIds = $this->pullAdditionalPicIds($data);

        $item->update($data);
        $this->syncAdditionalPics($item, $extraPicIds);

        return back()->with('status', 'Task diperbarui.');
    }

    public function destroyItem(WorkItem $item)
    {
        $item->delete();

        return back()->with('status', 'Task dihapus.');
    }

    /**
     * Drag-drop antar kolom board — padanan drop handler kanban di
     * prototype. Endpoint kecil terpisah dari updateItem() (yang butuh
     * WorkItemRequest lengkap) soalnya drag cuma ganti 1 kolom
     * (`progress`), dipanggil lewat fetch() JS di board, bukan submit
     * form biasa.
     */
    public function updateProgress(Request $request, WorkItem $item)
    {
        $data = $request->validate([
            'progress' => ['required', Rule::in(WorkItem::PROGRESS_OPTIONS)],
        ]);

        $item->update($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /** Simpan note inline dari tabel tracker (blur textarea) — fetch JSON. */
    public function updateNote(Request $request, WorkItem $item)
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:5000']]);

        $item->update(['notes' => $data['notes'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    /**
     * Ambil `additional_pic_ids` keluar dari data yang divalidasi (bukan
     * kolom tabel `work_items`). Mengembalikan null kalau field tidak dikirim
     * sama sekali, supaya update lain (mis. dari script/test) tidak menghapus
     * PIC tambahan yang sudah ada.
     */
    private function pullAdditionalPicIds(array &$data): ?array
    {
        if (! array_key_exists('additional_pic_ids', $data)) {
            return null;
        }

        $ids = array_map('intval', $data['additional_pic_ids'] ?? []);
        unset($data['additional_pic_ids']);

        return $ids;
    }

    /**
     * Simpan PIC tambahan. Kalau ada PIC tambahan, catatan teks lama
     * (`additional_pic`, termasuk penanda "ALL TEAM") dikosongkan: task itu
     * sekarang punya PIC nyata, dan teks lama tidak boleh menyimpang darinya.
     */
    private function syncAdditionalPics(WorkItem $item, ?array $ids): void
    {
        if ($ids === null) {
            return;
        }

        $item->additionalPics()->sync($ids);

        if ($ids !== [] && $item->additional_pic !== null) {
            $item->update(['additional_pic' => null]);
        }
    }

    private function nextItemNo(?int $projectId, ?string $section): int
    {
        return WorkItem::query()
            ->where('project_id', $projectId)
            ->where('section', $section ?? '')
            ->max('item_no') + 1;
    }
}