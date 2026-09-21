<?php

namespace Tests\Concerns;

use App\Models\DashboardAccess;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Helper data uji yang dipakai bersama oleh semua tes fitur.
 *
 * `company()` meniru 5 akun DemoSeeder (Owner, Kanaya/manajer, Rania/HRD,
 * Aldora & Gepeng/karyawan) beserta matriks `dashboard_access`-nya, tetapi
 * TANPA data demo lain, sehingga setiap tes mulai dari kondisi bersih dan
 * deterministik (tidak bergantung pada tanggal seeder dijalankan).
 */
trait CreatesWsmFixtures
{
    /** Senin, 10:00 — hari kerja tetap supaya tes absensi tidak bergantung pada tanggal asli. */
    protected const WORKDAY = '2026-09-21 10:00:00';

    protected function freezeWorkday(string $at = self::WORKDAY): Carbon
    {
        $now = Carbon::parse($at);
        Carbon::setTestNow($now);

        return $now;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string,string>  $modules  modul => level (view|manage)
     * @param  array<string,mixed>  $attributes
     */
    protected function makeUser(string $role = 'karyawan', array $modules = [], ?int $managerId = null, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => $role,
            'manager_id' => $managerId,
            'salary_base' => 6000000,
            'target_hours_per_day' => 8,
            'flat_overtime_rate' => 40000,
            'annual_leave_entitlement' => 12,
        ], $attributes));

        foreach ($modules as $module => $level) {
            DashboardAccess::create(['user_id' => $user->id, 'module' => $module, 'level' => $level]);
        }

        return $user;
    }

    protected function grant(User $user, string $module, string $level): void
    {
        DashboardAccess::updateOrCreate(
            ['user_id' => $user->id, 'module' => $module],
            ['level' => $level],
        );

        $user->unsetRelation('dashboardAccess');
    }

    /**
     * Baris `office_settings` seperti OfficeSettingSeeder (Depok, radius 200 m,
     * jam kerja 09:30-20:00, toleransi 15 menit, wajib 480 menit).
     *
     * @param  array<string,mixed>  $overrides
     */
    protected function officeSetting(array $overrides = []): OfficeSetting
    {
        // `id` tidak fillable dan MySQL tidak me-reset AUTO_INCREMENT antar tes, jadi id 1
        // (yang dicari OfficeSetting::current()) harus diisi eksplisit lewat forceFill.
        $setting = OfficeSetting::query()->find(1) ?? (new OfficeSetting)->forceFill(['id' => 1]);

        $setting->forceFill(array_merge([
            'office_name' => 'WSM Office',
            'address' => 'Jl. Raya Tapos No.43, Depok',
            'latitude' => -6.406876513053351,
            'longitude' => 106.88798145029513,
            'radius_meters' => 200,
            'geo_attendance_enabled' => true,
            'enforce_radius' => true,
            'work_start_time' => '09:30:00',
            'normal_end_time' => '20:00:00',
            'late_tolerance_minutes' => 15,
            'required_work_minutes' => 480,
            'shortage_deduction_rate' => 0,
        ], $overrides))->save();

        return $setting;
    }

    /**
     * Lima akun standar (password `password`, sama dengan DemoSeeder):
     * owner, manajer (Kanaya), hrd (Rania), aldora (karyawan, work=manage),
     * gepeng (karyawan, work=view). Aldora & Gepeng bawahan Kanaya; Kanaya
     * & Rania bawahan Owner.
     *
     * @return array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User}
     */
    protected function company(): array
    {
        $owner = $this->makeUser('owner', [], null, ['name' => 'Whisnu Santika', 'email' => 'owner@wsm.local']);

        $manajer = $this->makeUser('manajer', [
            'people' => 'view',
            'budget' => 'manage',
            'royalty' => 'manage',
            'kpi' => 'manage',
            'contracts' => 'manage',
            'payroll' => 'manage',
            'legal' => 'view',
            'it' => 'view',
        ], $owner->id, ['name' => 'Kanaya', 'email' => 'kanaya@wsm.local', 'salary_base' => 9500000, 'flat_overtime_rate' => 60000]);

        $hrd = $this->makeUser('hrd', [
            'people' => 'manage',
            'recruitment' => 'manage',
            'kpi' => 'view',
        ], $owner->id, ['name' => 'Rania', 'email' => 'rania@wsm.local', 'salary_base' => 7000000, 'flat_overtime_rate' => 45000]);

        $aldora = $this->makeUser('karyawan', ['work' => 'manage'], $manajer->id, ['name' => 'Aldora', 'email' => 'aldora@wsm.local']);
        $gepeng = $this->makeUser('karyawan', ['work' => 'view'], $manajer->id, ['name' => 'Gepeng', 'email' => 'gepeng@wsm.local', 'salary_base' => 6500000]);

        return compact('owner', 'manajer', 'hrd', 'aldora', 'gepeng');
    }

    /**
     * Akun Developer (seperti Arga di TestingAccountsSeeder): role `developer`,
     * Manage ke semua modul dashboard, atasannya Owner.
     *
     * @param  array<string,mixed>  $attributes
     */
    protected function makeDeveloper(User $owner, array $attributes = []): User
    {
        return $this->makeUser(
            'developer',
            array_fill_keys(array_keys(DashboardAccess::MODULES), 'manage'),
            $owner->id,
            array_merge(['name' => 'Arga', 'email' => 'arga@wsm.local'], $attributes),
        );
    }

    /** Koordinat persis di titik kantor (jarak 0 m). */
    protected function officeCoordinates(): array
    {
        return ['lat' => -6.406876513053351, 'lng' => 106.88798145029513];
    }

    /** Koordinat ±11 km dari kantor — jelas di luar radius 200 m. */
    protected function farCoordinates(): array
    {
        return ['lat' => -6.30, 'lng' => 106.80];
    }
}