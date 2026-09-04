<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder
 * ---------------------------------------------------------------------
 * Isi data contoh: 1 Owner, 1 Manajer, 2 Karyawan — biar bisa langsung
 * dicoba login. Jalankan: php artisan db:seed --class=DemoSeeder
 * PENTING: ganti password default sebelum dipakai beneran.
 * ---------------------------------------------------------------------
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Owner WSM',
            'email' => 'owner@wsm.local',
            'password' => Hash::make('password'),
            'role' => 'owner',
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
    }
}