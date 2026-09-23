<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Sudah dipakai operasional (2026-09) — seed default sekarang
        // CUMA akun asli (Owner + Arga/developer), tanpa data testing.
        // DemoSeeder & TestingAccountsSeeder tetap ada di repo untuk
        // lokal/dev (dan masih dipakai test suite lewat
        // `$this->seed(DemoSeeder::class)` langsung), tapi TIDAK lagi
        // ikut default `db:seed` / `migrate:fresh --seed`.
        $this->call([
            OfficeSettingSeeder::class,
            ProductionSeeder::class,
        ]);
    }
}