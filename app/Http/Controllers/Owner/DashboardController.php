<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;

/**
 * DashboardController (Owner)
 * ---------------------------------------------------------------------
 * Halaman ringkasan utama Owner. Masih shell/placeholder (Fase 0) —
 * belum ada data sungguhan karena modul sumber datanya belum dibangun.
 * ---------------------------------------------------------------------
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('owner.dashboard');
    }
}