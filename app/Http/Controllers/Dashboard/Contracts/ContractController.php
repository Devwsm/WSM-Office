<?php

namespace App\Http\Controllers\Dashboard\Contracts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Contracts\ContractRequest;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * ContractController (Dashboard > Contract Monitoring)
 * ---------------------------------------------------------------------
 * Fase 11 — CRUD kontrak karyawan (upload file + tanggal mulai/selesai
 * + catatan), padanan `saveContract()` di prototype v18. BEDA dari
 * prototype: file disimpan di storage server (`Storage::disk('public')`,
 * lihat catatan lengkap di migration `employee_contracts`), bukan blob
 * IndexedDB browser — konsekuensinya upload FILE BENERAN (multipart),
 * ini controller PERTAMA di codebase ini yang pakai `$request->file()`
 * (sebelumnya cuma ada upload foto base64 dari kamera di
 * `Employee\AttendanceController::storePhoto()`, beda mekanisme).
 *
 * Gate 'view'/'manage' modul 'contracts' — sama pola persis modul
 * 'work'/'kpi'.
 * ---------------------------------------------------------------------
 */
class ContractController extends Controller
{
    public function index(Request $request)
    {
        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        $contracts = EmployeeContract::query()
            ->with(['employee', 'uploader'])
            ->when($selectedEmployeeId, fn($q) => $q->where('employee_id', $selectedEmployeeId))
            ->orderByDesc('end_date')
            ->paginate(15)
            ->withQueryString();

        $employees = $this->employees();

        return view('dashboard.contracts.index', [
            'contracts' => $contracts,
            'employees' => $employees,
            'selectedEmployeeId' => $selectedEmployeeId,
        ]);
    }

    public function create()
    {
        return view('dashboard.contracts.create', ['employees' => $this->employees()]);
    }

    public function store(ContractRequest $request)
    {
        $data = $request->validated();
        $file = $request->file('file');

        $data['file_path'] = $file->store('contracts/' . $data['employee_id'], 'public');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getClientMimeType();
        $data['size_bytes'] = $file->getSize();
        $data['uploaded_by'] = Auth::id();
        unset($data['file']);

        EmployeeContract::create($data);

        return redirect()->route('dashboard.contracts.index')->with('status', 'Kontrak berhasil diupload.');
    }

    public function edit(EmployeeContract $contract)
    {
        return view('dashboard.contracts.edit', ['contract' => $contract, 'employees' => $this->employees()]);
    }

    public function update(ContractRequest $request, EmployeeContract $contract)
    {
        $data = $request->validated();

        if ($request->hasFile('file')) {
            // File lama dihapus DULU (bukan dibiarin numpuk) — kontrak
            // biasanya cuma butuh versi terbaru, revisi lama gak perlu
            // ditelusuri balik lewat sistem ini.
            Storage::disk('public')->delete($contract->file_path);

            $file = $request->file('file');
            $data['file_path'] = $file->store('contracts/' . $data['employee_id'], 'public');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getClientMimeType();
            $data['size_bytes'] = $file->getSize();
        }
        unset($data['file']);

        $contract->update($data);

        return redirect()->route('dashboard.contracts.index')->with('status', 'Kontrak berhasil diperbarui.');
    }

    public function destroy(EmployeeContract $contract)
    {
        Storage::disk('public')->delete($contract->file_path);
        $contract->delete();

        return back()->with('status', 'Kontrak berhasil dihapus.');
    }

    private function employees()
    {
        return User::query()->orderBy('name')->get(['id', 'name']);
    }
}