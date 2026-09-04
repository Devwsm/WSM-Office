<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * OrganizationController (Owner)
 * ---------------------------------------------------------------------
 * Fase 2 — tampilkan org-chart dari relasi manager_id yang sudah ada di
 * tabel users. Tree dibangun di memori (jumlah karyawan masih kecil,
 * belum perlu query rekursif/CTE). Karyawan nonaktif (soft deleted)
 * sengaja tidak ikut tampil di chart.
 * ---------------------------------------------------------------------
 */
class OrganizationController extends Controller
{
    public function index()
    {
        $users = User::query()->orderBy('name')->get(['id', 'name', 'role', 'division', 'job_title', 'manager_id']);

        $byManager = $users->groupBy(fn(User $user) => $user->manager_id ?? 'root');

        $roots = $byManager->get('root', collect());

        return view('owner.organization.index', [
            'roots' => $roots,
            'byManager' => $byManager,
            'totalKaryawan' => $users->count(),
        ]);
    }
}