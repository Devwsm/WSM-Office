<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

/**
 * HomeController (Employee)
 * ---------------------------------------------------------------------
 * Halaman utama sisi Karyawan/Manajer. Masih shell/placeholder (Fase 0).
 * Logic clock-in/out (foto + geolocation) masuk di Fase 4.
 * ---------------------------------------------------------------------
 */
class HomeController extends Controller
{
    public function index()
    {
        return view('employee.home');
    }
}