<?php

namespace Database\Seeders;

use App\Models\DashboardAccess;
use App\Models\Memo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ProductionSeeder
 * ---------------------------------------------------------------------
 * Seeder untuk sistem yang SUDAH dipakai operasional — menggantikan
 * DemoSeeder + TestingAccountsSeeder di alur `php artisan migrate:fresh
 * --seed` (lihat DatabaseSeeder). Isinya akun ASLI:
 *
 * - Ancha (ancha@whisnusantika.com)     — role `owner`.
 * - Arga  (it.wsm2026@gmail.com)        — role `developer`, Manage ke
 *   SEMUA modul dashboard (butuh baris `dashboard_access`, beda dari
 *   Owner yang hak aksesnya hardcode di kode — lihat User::accessLevel()).
 * - Sisa roster dari daftar kontak WhatsApp yang dikirim user
 *   (2026-09-23): SEMUA diberi role default `karyawan` dan manager_id
 *   = Ancha, KARENA role/jabatan/divisi masing-masing belum
 *   dikonfirmasi. Owner/Arga perlu menyesuaikan role & divisi tiap
 *   orang lewat halaman People setelah login pertama.
 *
 * CATATAN (perlu dicek manual sebelum dipakai):
 * - Nama-nama "WSM X" di bawah adalah label kontak WhatsApp, BUKAN
 *   dipastikan nama asli orangnya — silakan diganti ke nama asli lewat
 *   halaman People kalau perlu, tidak mempengaruhi login (pakai email).
 *
 * Plus 1 memo pinned "Welcome WSM" (v1) dari Arga selaku developer,
 * menggantikan popup "Masih tahap uji coba" yang sudah dimatikan lewat
 * `WOS_PREVIEW_MODE=false` di `.env` produksi (README Bab 4.1 no. 2).
 *
 * PENTING: password di bawah ini SEMENTARA (`must_change_password` =
 * true) — WAJIB diganti begitu masing-masing akun login pertama kali.
 *
 * DemoSeeder & TestingAccountsSeeder TIDAK dihapus dari repo — tetap
 * dipakai test suite (`PasswordResetTest`, dll. men-seed keduanya
 * langsung lewat `$this->seed(DemoSeeder::class)`), tapi sudah TIDAK
 * lagi dipanggil dari DatabaseSeeder, jadi tidak ikut ke seed default
 * (`db:seed` / `migrate:fresh --seed`) mulai sekarang.
 *
 * Aman dijalankan berulang (updateOrCreate / firstOrCreate memo).
 * ---------------------------------------------------------------------
 */
class ProductionSeeder extends Seeder
{
    /** Password sementara — WAJIB diganti (dipaksa lewat must_change_password). */
    private const TEMP_PASSWORD = 'GantiSegera#WSM2026';

    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'ancha@whisnusantika.com'],
            [
                'name' => 'Ancha',
                'password' => Hash::make(self::TEMP_PASSWORD),
                'must_change_password' => true,
                'role' => 'owner',
                'division' => null,
                'job_title' => null,
                'manager_id' => null,
            ],
        );

        $arga = User::query()->updateOrCreate(
            ['email' => 'it.wsm2026@gmail.com'],
            [
                'name' => 'Arga',
                'password' => Hash::make(self::TEMP_PASSWORD),
                'must_change_password' => true,
                'role' => 'developer',
                'division' => 'IT',
                'job_title' => 'Developer',
                'manager_id' => $owner->id,
            ],
        );

        // Developer akses modul dashboard-nya datang dari tabel
        // dashboard_access (bukan hard-code seperti Owner) — Manage ke
        // semua modul, sama seperti pola TestingAccountsSeeder lama.
        foreach (array_keys(DashboardAccess::MODULES) as $module) {
            DashboardAccess::query()->updateOrCreate(
                ['user_id' => $arga->id, 'module' => $module],
                ['level' => 'manage', 'granted_by' => $owner->id],
            );
        }

        // Sisa roster dari daftar kontak WhatsApp — role default
        // `karyawan`, manager_id = Ancha (Owner). Belum dikasih akses
        // dashboard_access apa pun (default 'none' ke semua modul)
        // sampai Owner tentukan role & akses masing-masing lewat UI.
        $roster = [
            ['name' => 'Ayu Dita Hartadi', 'email' => 'ayudita@whisnusantika.com'],
            ['name' => 'WS Inner Circle', 'email' => 'wsinnercircle@whisnusantika.com'],
            ['name' => 'WSM Ikhbal', 'email' => 'ikhbal@whisnusantika.com'],
            ['name' => 'WSM Jasmine', 'email' => 'management@whisnusantika.com'],
            ['name' => 'WSM Kanaya', 'email' => 'office@whisnusantika.com'],
            ['name' => 'WSM Legal', 'email' => 'legal@whisnusantika.com'],
            ['name' => 'WSM Aldora', 'email' => 'team@whisnusantika.com'],
            ['name' => 'WSM Gerry', 'email' => 'creative@whisnusantika.com'],
            ['name' => 'WSM Tour', 'email' => 'tour@whisnusantika.com'],
        ];

        foreach ($roster as $person) {
            User::query()->updateOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => Hash::make(self::TEMP_PASSWORD),
                    'must_change_password' => true,
                    'role' => 'karyawan',
                    'division' => null,
                    'job_title' => null,
                    'manager_id' => $owner->id,
                ],
            );
        }

        // Memo Welcome WSM v1 — dari Arga selaku developer, menggantikan
        // popup "Masih tahap uji coba" yang sudah dimatikan.
        Memo::query()->updateOrCreate(
            ['type' => 'memo', 'title' => 'Welcome WSM v1'],
            [
                'content' => "Selamat datang di WSM Office System!\n\nSistem ini sekarang sudah resmi dipakai untuk operasional harian WSM — absensi, pengajuan izin/cuti/lembur, Work Control, memo, dan modul lainnya sudah aktif. Kalau ada kendala teknis atau butuh akun baru, hubungi IT/Developer (Arga).",
                'pinned' => true,
                'audience' => 'semua',
                'active' => true,
                'created_by' => $arga->id,
            ],
        );
    }
}