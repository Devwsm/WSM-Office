<?php

namespace App\Http\Controllers\Dashboard\Kpi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Kpi\KpiRequest;
use App\Models\Kpi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * KpiController (Dashboard > KPI & Performance)
 * ---------------------------------------------------------------------
 * Fase 10 — sisi ADMIN kelola KPI seluruh tim, padanan `saveKpi()` di
 * prototype v18. Menutup satu-satunya bagian yang bikin kartu "My KPI"
 * di Home (Fase 9 App Mode quick win) selalu kosong — lihat catatan
 * `employee/_kpi.blade.php`.
 *
 * Gate 'view'/'manage' modul 'kpi' — sama pola persis
 * MemoController/WorkTrackerBoardController/MeetingController. Grup
 * route-nya HARUS terdaftar sebelum '/{module}' generik di
 * routes/web.php, sama alasan yang sama kayak grup 'work'.
 * ---------------------------------------------------------------------
 */
class KpiController extends Controller
{
    public function index(Request $request)
    {
        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        $kpis = Kpi::query()
            ->with(['employee', 'creator'])
            ->when($selectedEmployeeId, fn($q) => $q->where('employee_id', $selectedEmployeeId))
            ->orderByRaw("FIELD(status, 'Active', 'Completed', 'Archived')")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        $employees = User::query()->orderBy('name')->get(['id', 'name']);

        return view('dashboard.kpi.index', [
            'kpis' => $kpis,
            'employees' => $employees,
            'selectedEmployeeId' => $selectedEmployeeId,
        ]);
    }

    public function create()
    {
        return view('dashboard.kpi.create', ['employees' => $this->employees()]);
    }

    public function store(KpiRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        Kpi::create($data);

        return redirect()->route('dashboard.kpi.index')->with('status', 'KPI berhasil ditambahkan.');
    }

    public function edit(Kpi $kpi)
    {
        return view('dashboard.kpi.edit', ['kpi' => $kpi, 'employees' => $this->employees()]);
    }

    public function update(KpiRequest $request, Kpi $kpi)
    {
        $kpi->update($request->validated());

        return redirect()->route('dashboard.kpi.index')->with('status', 'KPI berhasil diperbarui.');
    }

    public function destroy(Kpi $kpi)
    {
        $kpi->delete();

        return back()->with('status', 'KPI berhasil dihapus.');
    }

    private function employees()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}