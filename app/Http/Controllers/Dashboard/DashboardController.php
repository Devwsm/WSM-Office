<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DashboardAccess;
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
 * ---------------------------------------------------------------------
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
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
                    // 'work' udah punya konten beneran (Fase 6b: MoM & Memo)
                    // — 6 modul lain masih placeholder generik sampai
                    // dibangun satu-satu di fase berikutnya.
                    'route' => $key === 'work' ? route('dashboard.work.index') : route('dashboard.show', $key),
                ];
            })
            ->filter(fn($m) => $m['level'] !== 'none')
            ->values();

        return view('dashboard.index', ['modules' => $modules]);
    }

    public function show(Request $request, string $module)
    {
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