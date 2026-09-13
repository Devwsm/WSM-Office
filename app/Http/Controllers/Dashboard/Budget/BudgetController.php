<?php

namespace App\Http\Controllers\Dashboard\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Budget\BudgetRequest;
use App\Models\Project;
use App\Models\ProjectBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * BudgetController (Dashboard > Project Budgeting)
 * ---------------------------------------------------------------------
 * Fase 13 — CRUD baris budget-vs-actual per project, padanan
 * `saveBudgetEntry()` di prototype v18. Listing dikelompokkan per
 * project (bukan flat list rata) — biar kartu "Total Budget vs Actual"
 * per project langsung kebaca, gak perlu jumlahin manual satu-satu.
 *
 * Gate 'view'/'manage' modul 'budget' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;

        $entries = ProjectBudget::query()
            ->with(['project', 'updater'])
            ->when($selectedProjectId, fn($q) => $q->where('project_id', $selectedProjectId))
            ->get()
            ->groupBy('project_id');

        $projects = Project::query()->orderBy('name')->get(['id', 'name']);

        return view('dashboard.budget.index', [
            'entriesByProject' => $entries,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
        ]);
    }

    public function create()
    {
        return view('dashboard.budget.create', ['projects' => $this->projects()]);
    }

    public function store(BudgetRequest $request)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        ProjectBudget::create($data);

        return redirect()->route('dashboard.budget.index')->with('status', 'Baris budget berhasil ditambahkan.');
    }

    public function edit(ProjectBudget $budget)
    {
        return view('dashboard.budget.edit', ['budget' => $budget, 'projects' => $this->projects()]);
    }

    public function update(BudgetRequest $request, ProjectBudget $budget)
    {
        $data = $request->validated();
        $data['updated_by'] = Auth::id();

        $budget->update($data);

        return redirect()->route('dashboard.budget.index')->with('status', 'Baris budget berhasil diperbarui.');
    }

    public function destroy(ProjectBudget $budget)
    {
        $budget->delete();

        return back()->with('status', 'Baris budget berhasil dihapus.');
    }

    private function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name']);
    }
}