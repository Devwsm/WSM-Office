<?php

namespace App\Http\Controllers\Dashboard\It;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PasswordResetController (Dashboard > IT > Reset Password)
 * ---------------------------------------------------------------------
 * Reset password karyawan oleh tim IT (modul `it`, level Manage), karena
 * belum ada reset mandiri lewat email. Alurnya:
 *   1. IT menekan "Reset" pada akun yang lupa password.
 *   2. Sistem membuat password sementara acak dan MENAMPILKANNYA SEKALI
 *      (flash session; hilang saat halaman dimuat ulang, tidak masuk
 *      audit log). IT menyampaikannya ke pemilik akun.
 *   3. Akun ditandai `must_change_password`: saat login berikutnya dia
 *      dipaksa mengganti password (middleware EnsurePasswordChanged).
 *   4. Semua sesi login lama akun itu dihapus, supaya sesi yang sedang
 *      terbuka (mis. di HP yang hilang) tidak lagi berlaku.
 *
 * Siapa boleh mereset siapa: User::canResetPasswordOf() — akun sendiri
 * tidak boleh (pakai Profil), akun Owner hanya oleh Owner, akun Developer
 * hanya oleh Owner atau Developer. Setiap reset tercatat di Audit Log.
 * ---------------------------------------------------------------------
 */
class PasswordResetController extends Controller
{
    /** Tanpa karakter yang mudah tertukar (0/O, 1/l/I) supaya gampang dibacakan. */
    private const ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function index(Request $request)
    {
        /** @var User $me */
        $me = Auth::user();

        $search = $request->string('q')->toString() ?: null;

        $employees = User::query()
            ->with('manager')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.it.password-resets.index', [
            'employees' => $employees,
            'search' => $search,
            'me' => $me,
        ]);
    }

    public function reset(User $employee)
    {
        /** @var User $actor */
        $actor = Auth::user();

        if ($actor->is($employee)) {
            return back()->with('error', 'Password akun sendiri diganti lewat halaman Profil, bukan lewat Reset Password.');
        }

        abort_unless($actor->canResetPasswordOf($employee), 403, 'Kamu tidak boleh mereset password akun ini.');

        $temporary = self::temporaryPassword();

        $employee->forceFill([
            'password' => $temporary,
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('sessions')->where('user_id', $employee->id)->delete();

        // Password sementara SENGAJA tidak ditulis ke audit log.
        AuditLog::record('Password direset', "Password {$employee->name} direset oleh {$actor->name}.", $actor);

        return redirect()
            ->route('dashboard.it.password-resets.index', request()->only('q'))
            ->with('reset_result', [
                'name' => $employee->name,
                'email' => $employee->email,
                'password' => $temporary,
            ]);
    }

    /** Password sementara acak, 12 karakter. */
    public static function temporaryPassword(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $password = '';

        for ($i = 0; $i < 12; $i++) {
            $password .= self::ALPHABET[random_int(0, $max)];
        }

        return $password;
    }
}