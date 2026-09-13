<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DashboardAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * DashboardController
 * ---------------------------------------------------------------------
 * Fase 6a — landing buat sistem akses per-modul yang BARU (beda dari
 * "Kelola Tim" yang nganterin ke attendance.recap/approval.leave —
 * itu tetap role-based apa adanya, sengaja gak digabung, lihat catatan
 * di migration dashboard_access).
 *
 * Modul yang muncul di sini murni berdasarkan User::accessLevel(),
 * BUKAN role — jadi karyawan biasa yang di-assign 'view' ke KPI bakal
 * lihat modul KPI di sini walau dia bukan Manajer/HRD/Owner.
 *
 * Isi tiap modul masih placeholder — konten beneran (Work Control =
 * MoM & Memo dst.) baru dibangun mulai Fase 6b, satu-satu.
 *
 * `Auth::user()` di-cast manual ke `User` (`/** @var User $user *\/`)
 * di kedua method — sama pola yang udah dipakai di
 * `Attendance\RecapController`/`Employee\ProfileController` — soalnya
 * return type aslinya `Authenticatable`, yang gak punya
 * `hasAnyDashboardAccess()`/`accessLevel()`/`canViewModule()` (method
 * custom model `User`). Cuma soal tipe data buat editor (Intelephense
 * P1013 "Undefined method"), bukan bug jalan/nggaknya kode.
 * ---------------------------------------------------------------------
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasAnyDashboardAccess()) {
            return redirect()->route('employee.home')
                ->with('error', 'Kamu belum punya akses dashboard modul apapun.');
        }

        $modules = collect(DashboardAccess::MODULES)
            ->map(function ($meta, $key) use ($user) {
                return [
                    'key' => $key,
                    'label' => $meta['label'],
                    'desc' => $meta['desc'],
                    'level' => $user->accessLevel($key),
                    // 'work', 'kpi', 'contracts', 'payroll', 'budget',
                    // 'royalty', 'legal' & 'it' udah punya konten beneran
                    // (Fase 6b/9, 10, 11, 12, 13, 14, 15) — semua 9
                    // modul dashboard_access sekarang udah gak ada yang
                    // placeholder generik lagi.
                    'route' => match ($key) {
                        'work' => route('dashboard.work.index'),
                        'kpi' => route('dashboard.kpi.index'),
                        'contracts' => route('dashboard.contracts.index'),
                        'payroll' => route('dashboard.payroll.index'),
                        'budget' => route('dashboard.budget.index'),
                        'royalty' => route('dashboard.royalty.index'),
                        'legal' => route('dashboard.legal.index'),
                        'it' => route('dashboard.it.index'),
                        default => route('dashboard.show', $key),
                    },
                ];
            })
            ->filter(fn($m) => $m['level'] !== 'none')
            ->values();

        return view('dashboard.index', ['modules' => $modules]);
    }

    public function show(Request $request, string $module)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! array_key_exists($module, DashboardAccess::MODULES) || ! $user->canViewModule($module)) {
            abort(403);
        }

        $meta = DashboardAccess::MODULES[$module];

        return view('dashboard.module', [
            'moduleKey' => $module,
            'label' => $meta['label'],
            'desc' => $meta['desc'],
            'level' => $user->accessLevel($module),
        ]);
    }
}