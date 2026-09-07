<?php

namespace Database\Seeders;

use App\Models\DashboardAccess;
use App\Models\LeaveRequest;
use App\Models\Memo;
use App\Models\MemoRead;
use App\Models\MemoThreadMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder
 * ---------------------------------------------------------------------
 * Isi data contoh: 1 Owner, 1 Manajer, 1 HRD, 2 Karyawan — biar bisa
 * langsung dicoba login & lihat org-chart (Fase 2). Sejak Fase 6a/6b
 * juga isi contoh dashboard_access & memo/MoM biar fitur itu langsung
 * kelihatan hasilnya abis seed (Aldora dikasih 'manage' modul Work
 * biar bisa dites bikin memo, Gepeng dikasih 'view' doang biar kelihatan
 * bedanya sama 'manage'). Jalankan:
 * php artisan db:seed --class=DemoSeeder
 * PENTING: ganti password default sebelum dipakai beneran.
 * ---------------------------------------------------------------------
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Whisnu Santika',
            'email' => 'owner@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'job_title' => 'CEO',
            'division' => 'CEO Office',
        ]);

        $manajer = User::create([
            'name' => 'Kanaya',
            'email' => 'kanaya@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'manajer',
            'division' => 'Operations',
            'job_title' => 'Secretary / Admin',
            'manager_id' => $owner->id,
            'join_date' => '2025-09-15',
        ]);

        $aldora = User::create([
            'name' => 'Aldora',
            'email' => 'aldora@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'division' => 'Marketing',
            'job_title' => 'Social Media',
            'manager_id' => $manajer->id,
            'join_date' => '2026-02-01',
        ]);

        $gepeng = User::create([
            'name' => 'Gepeng',
            'email' => 'gepeng@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'division' => 'Creative',
            'job_title' => 'Visual Designer',
            'manager_id' => $manajer->id,
            'join_date' => '2026-05-20',
        ]);

        User::create([
            'name' => 'Rania',
            'email' => 'rania@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'hrd',
            'division' => 'Human Resources',
            'job_title' => 'HR & Recruitment',
            'manager_id' => $owner->id,
            'join_date' => '2026-04-01',
        ]);

        // --- Fase 6a: contoh dashboard_access ---
        // Aldora (karyawan biasa) dikasih 'manage' modul Work Control
        // walau jabatannya bukan Manajer/HRD/Owner — ini contoh nyata
        // kenapa sistemnya per-user, bukan per-role. Gepeng cuma 'view'
        // biar kelihatan bedanya (gak ada tombol tambah/edit/hapus).
        DashboardAccess::create([
            'user_id' => $aldora->id,
            'module' => 'work',
            'level' => 'manage',
            'granted_by' => $owner->id,
        ]);
        DashboardAccess::create([
            'user_id' => $gepeng->id,
            'module' => 'work',
            'level' => 'view',
            'granted_by' => $owner->id,
        ]);

        // --- Fase 6b: contoh Memo & MoM ---
        $welcomeMemo = Memo::create([
            'type' => 'memo',
            'title' => 'Selamat datang di WSM Office System',
            'content' => "Halo tim! Mulai sekarang absensi, pengajuan izin/cuti, dan pengumuman internal dipusatkan di sini. Kalau ada kendala, hubungi HRD.",
            'pinned' => true,
            'created_by' => $owner->id,
        ]);
        Memo::create([
            'type' => 'mom',
            'title' => 'Kickoff Project Q3',
            'content' => "Bahas timeline & pembagian tugas project Q3. Draft brief dikirim H+1 ke masing-masing PIC.",
            'meeting_date' => now()->subDays(3)->toDateString(),
            'attendees' => 'Whisnu, Kanaya, Aldora',
            'created_by' => $manajer->id,
        ]);

        // --- Fase 8: contoh thread reply & status baca, biar Memo Forum
        // langsung kelihatan hasilnya abis seed (sama pola kayak
        // dashboard_access di atas). Aldora reply ke memo welcome →
        // muncul di badge unread sidebar "Work Control" (siapa pun yang
        // manage-level, di seed ini cuma Owner & Aldora) SAMPAI Aldora
        // sendiri (yang manage) buka /dashboard/work. Gepeng udah baca
        // tapi belum reply. Rania (HRD, gak punya akses modul work sama
        // sekali) SENGAJA gak disentuh di sini — dia tetap bisa lihat &
        // reply dari Home walau gak punya dashboard_access modul work.
        MemoThreadMessage::create([
            'memo_id' => $welcomeMemo->id,
            'user_id' => $aldora->id,
            'message' => 'Siap, izin cuti kemarin juga udah kelihatan approve-nya di sini. Makasih!',
        ]);
        MemoRead::create([
            'memo_id' => $welcomeMemo->id,
            'user_id' => $gepeng->id,
            'read_at' => now()->subDay(),
        ]);

        // --- Fase 8: contoh cuti tim bulan ini — buat demo banner Home.
        // Cuma dibuat kalau tanggal seed-nya masih dalam bulan berjalan
        // (biar gak numpuk cuti "basi" tiap kali db:seed diulang di
        // bulan yang beda).
        if (now()->day <= 25) {
            $gepengLeave = LeaveRequest::create([
                'user_id' => $gepeng->id,
                'type' => 'cuti_tahunan',
                'start_date' => now()->addDays(3)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'work_days' => 2,
                'reason' => 'Acara keluarga',
                'status' => 'pending',
            ]);
            $gepengLeave->approveBy($manajer);
        }
    }
}