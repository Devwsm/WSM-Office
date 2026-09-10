<?php

namespace App\Http\Controllers\Dashboard\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Work\ProjectRequest;
use App\Http\Requests\Dashboard\Work\WorkItemRequest;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkItem;
use Illuminate\Http\Request;
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
    public function index(Request $request)
    {
        $projects = Project::query()->orderBy('name')->get();

        $selectedProjectId = $request->integer('project_id') ?: null;

        $items = WorkItem::query()
            ->with(['project', 'pic'])
            ->when($selectedProjectId, fn($q) => $q->where('project_id', $selectedProjectId))
            ->orderBy('due_date')
            ->orderBy('item_no')
            ->get()
            ->groupBy('progress');

        $columns = collect(WorkItem::PROGRESS_OPTIONS)->mapWithKeys(
            fn(string $status) => [$status => $items->get($status, collect())]
        );

        $employees = User::query()->orderBy('name')->get(['id', 'name']);

        return view('dashboard.work.tracker.index', [
            'projects' => $projects,
            'columns' => $columns,
            'employees' => $employees,
            'selectedProjectId' => $selectedProjectId,
        ]);
    }

    // --- Project CRUD (modal "Kelola Projects" di board) ---

    public function storeProject(ProjectRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Project::uniqueSlugFrom($data['name']);
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

    // --- WorkItem CRUD ---

    public function storeItem(WorkItemRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['item_no'] = $this->nextItemNo($data['project_id'] ?? null, $data['section'] ?? null);

        WorkItem::create($data);

        return back()->with('status', 'Task ditambahkan.');
    }

    public function updateItem(WorkItemRequest $request, WorkItem $item)
    {
        $item->update($request->validated());

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

    private function nextItemNo(?int $projectId, ?string $section): int
    {
        return WorkItem::query()
            ->where('project_id', $projectId)
            ->where('section', $section)
            ->max('item_no') + 1;
    }
}