<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder
 * ---------------------------------------------------------------------
 * Isi data contoh: 1 Owner, 1 Manajer, 1 HRD, 2 Karyawan — biar bisa
 * langsung dicoba login & lihat org-chart (Fase 2). Jalankan:
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

        User::create([
            'name' => 'Aldora',
            'email' => 'aldora@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'karyawan',
            'division' => 'Marketing',
            'job_title' => 'Social Media',
            'manager_id' => $manajer->id,
            'join_date' => '2026-02-01',
        ]);

        User::create([
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
    }
}