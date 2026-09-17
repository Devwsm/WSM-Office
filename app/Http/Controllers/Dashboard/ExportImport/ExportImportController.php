<?php

namespace App\Http\Controllers\Dashboard\ExportImport;

use App\Http\Controllers\Controller;
use App\Support\ExportImport\ExportCatalog;
use Illuminate\Http\Request;

/**
 * ExportImportController (Dashboard > Export & Import Center)
 * ---------------------------------------------------------------------
 * Batch 0 — landing page tunggal yang nampilin kartu export/import
 * buat SEMUA modul (lihat ExportCatalog::CATALOG), difilter sesuai
 * dashboard_access user yang login. TIDAK ada gerbang `module:` khusus
 * di route index-nya sendiri (sama kayak ModuleDashboardController
 * root '/dashboard') — halaman ini murni "menu", isinya yang
 * disaring per-kartu lewat ExportCatalog::visibleFor().
 *
 * Aksi export/import beneran (download, preview, commit) BELUM ada di
 * sini — nyusul satu-satu di Batch 1 (export Excel), Batch 2 (export
 * PDF), Batch 3 (import), masing-masing kemungkinan jadi
 * controller/route sendiri per modul (bukan numpuk semua di sini)
 * biar konsisten sama pola 1-controller-per-modul yang sudah dipakai
 * di seluruh Dashboard/*.
 * ---------------------------------------------------------------------
 */
class ExportImportController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.export-import.index', [
            'catalog' => ExportCatalog::visibleFor($request->user()),
        ]);
    }
}