<?php

namespace App\Http\Controllers\Dashboard\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Budget\BudgetRequest;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Support\BudgetReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * BudgetController (Dashboard > Project Budgeting)
 * ---------------------------------------------------------------------
 * Fase 13 — CRUD baris budget-vs-actual per project, padanan
 * `saveBudgetEntry()` di prototype v18. Listing dikelompokkan per
 * project (bukan flat list rata) — biar kartu "Total Budget vs Actual"
 * per project langsung kebaca, gak perlu jumlahin manual satu-satu.
 *
 * Ringkasan (Total/Actual/Remaining/Utilization), grafik Budget vs Actual
 * (per kategori / project / lagu), dan PDF laporan dihitung oleh
 * App\Support\BudgetReport — satu sumber angka untuk layar dan kertas.
 *
 * Gate 'view'/'manage' modul 'budget' — sama pola persis modul lain.
 * ---------------------------------------------------------------------
 */
class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;
        $group = BudgetReport::normalizeGroup($request->query('group'));

        $report = BudgetReport::forProject($selectedProjectId);

        return view('dashboard.budget.index', [
            'report' => $report,
            'totals' => $report->totals(),
            'chart' => $report->chart($group),
            'group' => $group,
            'groups' => BudgetReport::GROUPS,
            'entriesByProject' => $report->byProject(),
            'projects' => $this->projects(),
            'selectedProjectId' => $selectedProjectId,
        ]);
    }

    /**
     * Laporan cetak (A4 landscape): ringkasan, grafik, lalu tabel per project.
     * Dibuka inline supaya langsung bisa dicetak atau disimpan dari viewer PDF.
     * Mengikuti filter project & pengelompokan grafik yang sedang dibuka.
     */
    public function pdf(Request $request)
    {
        $selectedProjectId = $request->integer('project_id') ?: null;
        $group = BudgetReport::normalizeGroup($request->query('group'));
        $project = $selectedProjectId ? Project::query()->find($selectedProjectId) : null;

        $report = BudgetReport::forProject($selectedProjectId);

        $filename = Str::slug('laporan-anggaran-' . ($project?->name ?? 'semua-project') . '-' . now()->format('Y-m-d')) . '.pdf';

        return Pdf::loadView('pdf.budget-report', [
            'totals' => $report->totals(),
            'chart' => $report->chart($group),
            'groupLabel' => BudgetReport::GROUPS[$group],
            'entriesByProject' => $report->byProject(),
            'scopeLabel' => $project?->name ?? 'Semua project',
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function create()
    {
        return view('dashboard.budget.create', ['projects' => $this->projects()] + $this->formSuggestions());
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
        return view('dashboard.budget.edit', ['budget' => $budget, 'projects' => $this->projects()] + $this->formSuggestions());
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

    /** Saran datalist form (kategori & lagu); di-include otomatis ke partial _form. */
    private function formSuggestions(): array
    {
        return [
            'categorySuggestions' => BudgetReport::categorySuggestions(),
            'songSuggestions' => BudgetReport::songSuggestions(),
        ];
    }

    private function projects()
    {
        return Project::query()->orderBy('name')->get(['id', 'name']);
    }
}