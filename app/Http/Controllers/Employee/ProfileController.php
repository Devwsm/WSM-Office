<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * ProfileController (Employee)
 * ---------------------------------------------------------------------
 * Tab "Profile" di bottom-nav app-mobile — sebelumnya placeholder
 * (`href="#"`, ditandai TODO Fase 1) sejak layout Karyawan pertama
 * dibuat. Prototype W.O.S 2.0 punya `renderEmployeeProfile()` isinya
 * data diri (nama/role/divisi) + form ganti password
 * (`pwCurrent`/`pwNew`/`pwConfirm`) — halaman ini disamakan ke situ,
 * SENGAJA belum termasuk foto profil (belum ada kolom/upload foto
 * karyawan di sistem ini sama sekali, beda topik dari halaman ini).
 *
 * `Auth::user()` di-cast manual ke `User` (`/** @var User $me *\/`) di
 * kedua method — sama pola yang udah dipakai di
 * `Attendance\RecapController` — soalnya return type aslinya
 * `Authenticatable`, yang gak punya `update()`/method custom model
 * `User` lain. Cuma soal tipe data buat editor (Intelephense
 * P1013 "Undefined method"), bukan bug jalan/nggaknya kode.
 * ---------------------------------------------------------------------
 */
class ProfileController extends Controller
{
    public function index()
    {
        /** @var User $me */
        $me = Auth::user();

        return view('employee.profile', [
            'user' => $me,
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        /** @var User $me */
        $me = Auth::user();

        $me->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'Password berhasil diganti.');
    }
}