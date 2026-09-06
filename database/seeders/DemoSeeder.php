<?php

namespace Database\Seeders;

use App\Models\DashboardAccess;
use App\Models\Memo;
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
        Memo::create([
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
    }
}