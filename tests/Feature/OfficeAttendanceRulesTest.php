<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Dua aturan absensi yang sekarang bisa diatur Owner (2026-09-29):
 * ukuran blok "Kurang Jam Kerja" dan saklar tutup-otomatis sesi lupa pulang.
 * Default-nya harus persis sama dengan perilaku lama (60 menit, aktif).
 */
class OfficeAttendanceRulesTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
    }

    private function shortDay(User $user, string $date = '2026-09-15'): void
    {
        // 09:30–13:30 = 240 menit dari wajib 480 -> kurang 240 menit.
        Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => "$date 09:30:00",
            'clock_out_at' => "$date 13:30:00",
        ]);
    }

    private function settingPayload(array $overrides = []): array
    {
        return array_merge([
            'office_name' => 'WSM Office',
            'address' => 'Jl. Contoh No. 1, Depok',
            'latitude' => -6.4,
            'longitude' => 106.9,
            'radius_meters' => 200,
            'geo_attendance_enabled' => '1',
            'enforce_radius' => '1',
            'work_start_time' => '09:30',
            'normal_end_time' => '20:00',
            'late_tolerance_minutes' => 15,
            'required_work_minutes' => 480,
            'payroll_work_days_divisor' => 22,
            'ceo_accent_color' => '#111111',
            'work_accent_color' => '#3558f4',
        ], $overrides);
    }

    private function save(array $overrides = [])
    {
        return $this->actingAs($this->p['owner'])
            ->patch(route('owner.office-settings.update'), $this->settingPayload($overrides));
    }

    // ---- ukuran blok -----------------------------------------------------

    public function test_default_block_is_still_sixty_minutes(): void
    {
        $this->assertSame(60, OfficeSetting::current()->shortageBlockMinutes());

        $this->shortDay($this->p['gepeng']);

        $result = Attendance::monthlyShortageBlocks($this->p['gepeng']->id, '2026-09');
        $this->assertSame(['total_shortage_minutes' => 240, 'blocks' => 4, 'remainder_minutes' => 0], $result);
    }

    public function test_block_size_changes_blocks_and_remainder(): void
    {
        $this->officeSetting(['shortage_block_minutes' => 45]);
        $this->shortDay($this->p['gepeng']);

        $result = Attendance::monthlyShortageBlocks($this->p['gepeng']->id, '2026-09');
        // 240 menit / 45 = 5 blok, sisa 15 menit belum genap satu blok.
        $this->assertSame(['total_shortage_minutes' => 240, 'blocks' => 5, 'remainder_minutes' => 15], $result);
    }

    public function test_history_page_uses_the_configured_block_size(): void
    {
        $this->officeSetting(['shortage_block_minutes' => 30]);
        $this->shortDay($this->p['gepeng']);

        $this->actingAs($this->p['gepeng'])->get('/app/riwayat')
            ->assertOk()
            ->assertViewHas('shortage', fn($s) => $s['blocks'] === 8)
            ->assertSee('dipotong per blok 30 menit');
    }

    public function test_zero_or_missing_block_size_falls_back_to_sixty(): void
    {
        $this->assertSame(60, (new OfficeSetting(['shortage_block_minutes' => 0]))->shortageBlockMinutes());
        $this->assertSame(60, (new OfficeSetting)->shortageBlockMinutes());
    }

    public function test_owner_can_save_block_size_and_it_is_validated(): void
    {
        $this->save(['shortage_block_minutes' => 30])->assertSessionHasNoErrors();
        $this->assertSame(30, OfficeSetting::current()->shortage_block_minutes);

        $this->save(['shortage_block_minutes' => 14])->assertSessionHasErrors('shortage_block_minutes');
        $this->save(['shortage_block_minutes' => 241])->assertSessionHasErrors('shortage_block_minutes');
        $this->save(['shortage_block_minutes' => 'abc'])->assertSessionHasErrors('shortage_block_minutes');
        $this->save(['shortage_block_minutes' => 15])->assertSessionHasNoErrors();
        $this->save(['shortage_block_minutes' => 240])->assertSessionHasNoErrors();
    }

    public function test_settings_page_shows_the_new_fields(): void
    {
        $this->actingAs($this->p['owner'])->get(route('owner.office-settings.edit'))
            ->assertOk()
            ->assertSee('name="shortage_block_minutes"', false)
            ->assertSee('name="auto_close_enabled"', false)
            ->assertSee('Tutup otomatis sesi yang lupa pulang');
    }

    // ---- form lama tidak mengubah aturan --------------------------------

    public function test_form_without_the_new_fields_keeps_the_stored_rules(): void
    {
        $this->officeSetting(['shortage_block_minutes' => 30, 'auto_close_enabled' => false]);

        // Persis payload halaman lama yang masih terbuka saat deploy: tanpa dua field baru.
        $this->save()->assertSessionHasNoErrors();

        $setting = OfficeSetting::current();
        $this->assertSame(30, $setting->shortage_block_minutes);
        $this->assertFalse($setting->auto_close_enabled);
    }

    // ---- tutup otomatis ---------------------------------------------------

    private function forgottenYesterday(): Attendance
    {
        return Attendance::create([
            'user_id' => $this->p['gepeng']->id,
            'date' => '2026-09-18',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-18 09:30:00',
        ]);
    }

    public function test_auto_close_is_on_by_default(): void
    {
        $this->assertTrue(OfficeSetting::current()->auto_close_enabled);

        $row = $this->forgottenYesterday();
        $this->actingAs($this->p['gepeng'])->get('/app/home')->assertOk();

        $this->assertTrue($row->refresh()->auto_closed);
    }

    public function test_auto_close_can_be_turned_off_and_leaves_the_session_open(): void
    {
        $this->officeSetting(['auto_close_enabled' => false]);
        $row = $this->forgottenYesterday();

        $this->actingAs($this->p['gepeng'])->get('/app/home')->assertOk();
        $this->actingAs($this->p['gepeng'])->get('/app/riwayat?bulan=2026-09')->assertOk();

        $row->refresh();
        $this->assertNull($row->clock_out_at);
        $this->assertFalse($row->auto_closed);
        $this->assertSame('lupa_absen_pulang', $row->statusKey());
    }

    public function test_owner_can_toggle_auto_close_through_the_form(): void
    {
        $this->save(['auto_close_enabled' => '0'])->assertSessionHasNoErrors();
        $this->assertFalse(OfficeSetting::current()->auto_close_enabled);

        $this->save(['auto_close_enabled' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue(OfficeSetting::current()->auto_close_enabled);
    }

    public function test_changes_are_audited_and_limited_to_office_settings_managers(): void
    {
        $this->actingAs($this->p['gepeng'])
            ->patch(route('owner.office-settings.update'), $this->settingPayload(['shortage_block_minutes' => 30]))
            ->assertForbidden();

        $this->assertSame(60, OfficeSetting::current()->shortage_block_minutes);

        $this->save(['shortage_block_minutes' => 30]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Pengaturan kantor diubah']);
    }
}