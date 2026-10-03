<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\UpdateAvatarRequest;
use App\Http\Requests\Employee\UpdatePasswordRequest;
use App\Http\Requests\Employee\UpdateThemeRequest;
use App\Models\User;
use App\Support\PrivateFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * ProfileController (Employee)
 * ---------------------------------------------------------------------
 * Tab "Profile" di bottom-nav app-mobile — sebelumnya placeholder
 * (`href="#"`, ditandai TODO Fase 1) sejak layout Karyawan pertama
 * dibuat. Prototype W.O.S 2.0 punya `renderEmployeeProfile()` isinya
 * data diri (nama/role/divisi) + form ganti password
 * (`pwCurrent`/`pwNew`/`pwConfirm`) — halaman ini disamakan ke situ.
 *
 * 2026-10-02 — ditambah foto profil (updateAvatar/destroyAvatar/avatar)
 * dan Personal Colors (updateTheme/resetTheme), padanan selisih #10.
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

    /**
     * Foto profil — padanan `handleSelfThumb()` di prototype. Disimpan di
     * disk private (PrivateFile) dan hanya keluar lewat route `avatar.show`
     * untuk user yang login; foto lama dihapus saat diganti.
     */
    public function updateAvatar(UpdateAvatarRequest $request)
    {
        /** @var User $me */
        $me = Auth::user();

        $old = $me->avatar_path;
        $me->update(['avatar_path' => PrivateFile::store($request->file('photo'), 'avatars')]);
        PrivateFile::delete($old);

        return back()->with('status', 'Foto profil diperbarui.');
    }

    public function destroyAvatar()
    {
        /** @var User $me */
        $me = Auth::user();

        PrivateFile::delete($me->avatar_path);
        $me->update(['avatar_path' => null]);

        return back()->with('status', 'Foto profil dihapus.');
    }

    /** Alirkan foto profil ke user yang sedang login (dipakai <img> di header & profil). */
    public function avatar(User $user)
    {
        abort_unless($user->hasAvatar(), 404);

        return PrivateFile::response($user->avatar_path);
    }

    /** Personal Colors — hanya mengubah tampilan App Mode milik user ini. */
    public function updateTheme(UpdateThemeRequest $request)
    {
        /** @var User $me */
        $me = Auth::user();

        $me->update(['theme_colors' => array_map('strtolower', $request->validated())]);

        return back()->with('status', 'Warna tampilan kamu disimpan.');
    }

    public function resetTheme()
    {
        /** @var User $me */
        $me = Auth::user();

        $me->update(['theme_colors' => null]);

        return back()->with('status', 'Warna tampilan dikembalikan ke bawaan.');
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        /** @var User $me */
        $me = Auth::user();

        $me->update([
            'password' => Hash::make($request->validated('password')),
            'must_change_password' => false,
        ]);

        return back()->with('status', 'Password berhasil diganti.');
    }
}