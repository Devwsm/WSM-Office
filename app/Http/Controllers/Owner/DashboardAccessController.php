<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DashboardAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DashboardAccessController (Owner)
 * ---------------------------------------------------------------------
 * Fase 6a — cuma Owner yang boleh assign/ubah akses modul milik orang
 * lain (dikunci di routes/web.php lewat middleware role:owner, bukan
 * grup role:manajer,owner,hrd kayak fitur lain). Owner sendiri gak
 * kelihatan di daftar karyawan yang bisa diedit di sini karena
 * akses Owner udah otomatis 'manage' semua modul (User::accessLevel())
 * — gak ada yang perlu di-assign.
 * ---------------------------------------------------------------------
 */
class DashboardAccessController extends Controller
{
    public function edit(User $employee)
    {
        if ($employee->isOwner()) {
            return redirect()->route('owner.employees.index')
                ->with('error', 'Owner otomatis punya akses penuh ke semua modul, tidak perlu di-assign.');
        }

        $current = $employee->dashboardAccess->keyBy('module');

        return view('owner.dashboard-access.edit', [
            'employee' => $employee,
            'modules' => DashboardAccess::MODULES,
            'current' => $current,
        ]);
    }

    public function update(Request $request, User $employee)
    {
        if ($employee->isOwner()) {
            return redirect()->route('owner.employees.index')
                ->with('error', 'Owner otomatis punya akses penuh ke semua modul, tidak perlu di-assign.');
        }

        $moduleKeys = array_keys(DashboardAccess::MODULES);

        $data = $request->validate([
            'access' => ['array'],
            'access.*' => ['nullable', 'in:view,manage'],
        ]);

        $levels = $data['access'] ?? [];

        DB::transaction(function () use ($employee, $moduleKeys, $levels) {
            foreach ($moduleKeys as $module) {
                $level = $levels[$module] ?? null;

                if ($level === null) {
                    // 'none' — hapus barisnya kalau ada, jangan simpan level='none'.
                    $employee->dashboardAccess()->where('module', $module)->delete();

                    continue;
                }

                $employee->dashboardAccess()->updateOrCreate(
                    ['module' => $module],
                    ['level' => $level, 'granted_by' => Auth::id()]
                );
            }
        });

        return redirect()->route('owner.employees.index')
            ->with('status', "Dashboard access {$employee->name} berhasil diperbarui.");
    }
}