<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * backfill_dashboard_access_for_manajer_hrd
 * ---------------------------------------------------------------------
 * 2026-09-09 — refactor "permission bukan role" (lihat README). Rekap
 * Absensi, Persetujuan Izin/Cuti/Lembur, & Rekrutmen PINDAH dari
 * `role:...` middleware ke `module:people,...`/`module:recruitment,...`
 * (dashboard_access). Tanpa migration ini, SEMUA user existing berrole
 * 'manajer'/'hrd' di database yang UDAH JALAN (bukan instalasi baru)
 * bakal LANGSUNG kehilangan akses pas migration ini di-deploy — role
 * mereka gak lagi otomatis buka apa-apa begitu route-nya diganti.
 *
 * Migration ini nyamain akses SETELAH refactor supaya PERSIS SAMA
 * dengan SEBELUM refactor (nol perubahan akses buat siapa pun di hari
 * deploy) — Owner tinggal cabut satu-satu lewat halaman "Dashboard
 * Access" yang udah ada kalau memang ada yang gak seharusnya punya
 * akses itu (itu justru tujuan refactor ini: sekarang BISA dicabut
 * per-orang, dulu enggak bisa sama sekali karena nempel ke role).
 *
 * Levelnya:
 * - role 'manajer' -> `people` level 'view'. Cukup buat masuk Rekap
 *   Absensi & layar Persetujuan (approve/reject tetap dicek relasi
 *   `manager_id` di controller, BUKAN oleh level ini — lihat
 *   App\Models\DashboardAccess doc-comment).
 * - role 'hrd' -> `people` level 'manage' (HRD butuh koreksi absensi,
 *   bukan cuma lihat) + `recruitment` level 'manage' (kerjaan utama
 *   HRD).
 * - Owner TIDAK disentuh — akses Owner selalu 'manage' semua modul,
 *   dihitung di kode (User::accessLevel()), bukan baris di tabel ini.
 *
 * Idempotent: pakai `insertOrIgnore` + kombinasi (user_id, module)
 * yang sudah dijamin unik sama migration `create_dashboard_access_table`
 * (comment di situ: "1 baris per user per modul"), jadi aman dijalankan
 * berkali-kali / re-run di staging tanpa bikin baris dobel.
 *
 * `down()` SENGAJA no-op — membatalkan migration ini artinya mencabut
 * akses banyak orang sekaligus secara diam-diam, itu keputusan Owner,
 * bukan sesuatu yang boleh kejadian otomatis lewat rollback.
 * ---------------------------------------------------------------------
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $manajerIds = DB::table('users')->where('role', 'manajer')->pluck('id');
        $hrdIds = DB::table('users')->where('role', 'hrd')->pluck('id');

        $rows = [];

        foreach ($manajerIds as $id) {
            $rows[] = [
                'user_id' => $id,
                'module' => 'people',
                'level' => 'view',
                'granted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($hrdIds as $id) {
            $rows[] = [
                'user_id' => $id,
                'module' => 'people',
                'level' => 'manage',
                'granted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $rows[] = [
                'user_id' => $id,
                'module' => 'recruitment',
                'level' => 'manage',
                'granted_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            DB::table('dashboard_access')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        // Sengaja no-op — lihat penjelasan di doc-comment atas.
    }
};