<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreEmployeeRequest;
use App\Http\Requests\Owner\UpdateEmployeeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * EmployeeController (Owner)
 * ---------------------------------------------------------------------
 * Fase 2 — CRUD karyawan/manajer/HRD + assign role & atasan (manager_id,
 * dipakai org-chart & alur approval cuti nanti). "Hapus" karyawan pakai
 * soft delete (kolom deleted_at) supaya riwayat (payroll, kontrak, dst.
 * di fase-fase selanjutnya) tidak ikut hilang — bahasanya di UI sengaja
 * "Nonaktifkan", bukan "Hapus".
 * ---------------------------------------------------------------------
 */
class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

        if ($request->boolean('nonaktif')) {
            $query->onlyTrashed();
        }

        if ($role = $request->string('role')->toString()) {
            $query->where('role', $role);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('division', 'like', "%{$search}%");
            });
        }

        $employees = $query->with('manager')->paginate(15)->withQueryString();

        return view('owner.employees.index', [
            'employees' => $employees,
            'filters' => $request->only(['role', 'q', 'nonaktif']),
        ]);
    }

    public function create()
    {
        // Atasan yang bisa dipilih: semua user aktif kecuali karyawan biasa
        // tidak dibatasi di sini — Owner bebas assign siapa saja jadi
        // atasan (mis. HRD di bawah Owner langsung).
        $managers = User::query()->orderBy('name')->get();

        return view('owner.employees.create', ['managers' => $managers]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('owner.employees.index')->with('status', 'Karyawan baru berhasil ditambahkan.');
    }

    public function edit(User $employee)
    {
        $managers = User::query()->where('id', '!=', $employee->id)->orderBy('name')->get();

        return view('owner.employees.edit', ['employee' => $employee, 'managers' => $managers]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee)
    {
        $data = $request->validated();

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $employee->update($data);

        return redirect()->route('owner.employees.index')->with('status', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(User $employee)
    {
        if ($employee->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menonaktifkan akun sendiri.');
        }

        // Lepas dulu bawahan yang manager_id-nya nunjuk ke user ini,
        // supaya org-chart tidak nunjuk ke akun nonaktif.
        User::where('manager_id', $employee->id)->update(['manager_id' => $employee->manager_id]);

        $employee->delete();

        return back()->with('status', "{$employee->name} dinonaktifkan.");
    }

    public function restore(int $employee)
    {
        $user = User::onlyTrashed()->findOrFail($employee);
        $user->restore();

        return back()->with('status', "{$user->name} diaktifkan kembali.");
    }
}