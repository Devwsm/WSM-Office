<?php

namespace Database\Seeders;

use App\Models\DashboardAccess;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * TestingAccountsSeeder
 * ---------------------------------------------------------------------
 * Akun testing tambahan, HANYA untuk lokal — TIDAK dipanggil dari
 * DatabaseSeeder dan tidak boleh ikut ke produksi (password `password`).
 *
 * Jalankan SETELAH DemoSeeder (butuh akun Owner yang sudah ada):
 *   php artisan db:seed --class=TestingAccountsSeeder
 *
 * Isi:
 * - Ancha (`ancha@wsm.test`): office manager, role `manajer`, Manage ke
 *   SEMUA 10 modul dashboard. Dijadikan "Atasan Langsung" di puncak
 *   struktur (langsung di bawah Owner) dan semua akun non-Owner lain
 *   dipindahkan ke bawahnya, supaya Rekap Absensi-nya (cakupan manajer =
 *   diri sendiri + seluruh bawahan turunan) mencakup semua orang tanpa
 *   mengubah kode. Persetujuan yang bisa ia putus tetap hanya milik
 *   bawahan langsungnya.
 * - Arga (`arga@wsm.test`): role `developer`, Manage ke SEMUA 10 modul
 *   dashboard, langsung di bawah Ancha.
 *
 * Aman dijalankan berulang (updateOrCreate).
 * ---------------------------------------------------------------------
 */
class TestingAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('role', 'owner')->orderBy('id')->first();

        if ($owner === null) {
            $this->command?->error('Akun Owner belum ada. Jalankan DemoSeeder dulu (php artisan migrate:fresh --seed).');

            return;
        }

        $ancha = User::updateOrCreate(
            ['email' => 'ancha@wsm.test'],
            [
                'name' => 'Ancha',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'role' => 'manajer',
                'division' => 'Operations',
                'job_title' => 'Office Manager',
                'manager_id' => $owner->id,
                'join_date' => '2025-01-06',
                'salary_base' => 9000000,
                'target_hours_per_day' => 8,
                'flat_overtime_rate' => 55000,
            ],
        );

        // Semua akun non-Owner yang sekarang langsung di bawah Owner (atau
        // tanpa atasan) pindah ke bawah Ancha, kecuali Ancha sendiri.
        User::query()
            ->where('id', '!=', $owner->id)
            ->where('id', '!=', $ancha->id)
            ->where(function ($query) use ($owner) {
                $query->whereNull('manager_id')->orWhere('manager_id', $owner->id);
            })
            ->update(['manager_id' => $ancha->id]);

        $arga = User::updateOrCreate(
            ['email' => 'arga@wsm.test'],
            [
                'name' => 'Arga',
                'password' => Hash::make('password'),
                'must_change_password' => false,
                'role' => 'developer',
                'division' => 'IT',
                'job_title' => 'Developer',
                'manager_id' => $ancha->id,
                'join_date' => '2025-06-20',
                'salary_base' => 3000000,
                'target_hours_per_day' => 8,
                'flat_overtime_rate' => 60000,
            ],
        );

        foreach ([$ancha, $arga] as $user) {
            foreach (array_keys(DashboardAccess::MODULES) as $module) {
                DashboardAccess::updateOrCreate(
                    ['user_id' => $user->id, 'module' => $module],
                    ['level' => 'manage', 'granted_by' => $owner->id],
                );
            }
        }
    }
}