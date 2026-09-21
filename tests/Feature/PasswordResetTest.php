<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\OfficeSettingSeeder;
use Database\Seeders\TestingAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Reset password oleh IT (modul `it`, Manage), kewajiban mengganti password
 * sementara (`must_change_password` + middleware EnsurePasswordChanged), dan
 * TestingAccountsSeeder (Ancha & Arga) untuk pengujian lokal.
 */
class PasswordResetTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        Storage::fake('local');
        $this->p = $this->company();
        $this->dev = $this->makeDeveloper($this->p['owner']);
    }

    private function reset(User $actor, User $target)
    {
        return $this->actingAs($actor)->post(route('dashboard.it.password-resets.reset', $target));
    }

    // ---- akses halaman ---------------------------------------------------

    public function test_the_reset_page_needs_manage_access_to_the_it_module(): void
    {
        $this->get(route('dashboard.it.password-resets.index'))->assertRedirect(route('login'));

        // Kanaya hanya View pada modul `it`; Aldora tidak punya akses sama sekali.
        $this->actingAs($this->p['manajer'])->get(route('dashboard.it.password-resets.index'))->assertForbidden();
        $this->actingAs($this->p['aldora'])->get(route('dashboard.it.password-resets.index'))->assertForbidden();

        $this->grant($this->p['manajer'], 'it', 'manage');
        $this->actingAs($this->p['manajer'])->get(route('dashboard.it.password-resets.index'))
            ->assertOk()
            ->assertSee('Reset Password')
            ->assertSee('Aldora');
    }

    public function test_view_only_it_access_cannot_reset_anything(): void
    {
        $this->reset($this->p['manajer'], $this->p['aldora'])->assertForbidden();

        $this->assertFalse($this->p['aldora']->fresh()->must_change_password);
    }

    public function test_the_reset_menu_only_shows_for_manage_access(): void
    {
        $link = route('dashboard.it.password-resets.index');

        $this->actingAs($this->p['manajer'])->get(route('dashboard.it.index'))->assertOk()->assertDontSee($link, false);
        $this->actingAs($this->dev)->get(route('dashboard.it.index'))->assertOk()->assertSee($link, false);
        $this->actingAs($this->p['owner'])->get(route('dashboard.it.changelog.index'))->assertOk()->assertSee($link, false);
    }

    public function test_the_page_has_a_guide_button_with_the_manage_label(): void
    {
        $this->actingAs($this->dev)
            ->get(route('dashboard.it.password-resets.index'))
            ->assertOk()
            ->assertSee('data-page-guide="password-reset"', false)
            ->assertSee('Akses kamu: Manage');
    }

    // ---- proses reset ----------------------------------------------------

    public function test_reset_creates_a_temporary_password_and_flags_the_account(): void
    {
        $aldora = $this->p['aldora'];
        $oldHash = $aldora->password;

        $response = $this->reset($this->dev, $aldora)->assertRedirect(route('dashboard.it.password-resets.index'));

        $result = session('reset_result');
        $this->assertSame('Aldora', $result['name']);
        $this->assertSame(12, strlen($result['password']));
        $this->assertMatchesRegularExpression('/^[a-km-zA-HJ-NP-Z2-9]{12}$/', $result['password']);

        $aldora->refresh();
        $this->assertNotSame($oldHash, $aldora->password);
        $this->assertTrue(Hash::check($result['password'], $aldora->password));
        $this->assertFalse(Hash::check('password', $aldora->password));
        $this->assertTrue($aldora->must_change_password);
    }

    public function test_the_temporary_password_is_shown_once_and_never_logged(): void
    {
        $this->reset($this->dev, $this->p['aldora']);
        $temporary = session('reset_result.password');

        // Halaman tujuan menampilkan password sementara sekali...
        $this->get(route('dashboard.it.password-resets.index'))
            ->assertOk()
            ->assertSee('Password Aldora sudah direset')
            ->assertSee('data-temporary-password', false)
            ->assertSee($temporary);

        // ...lalu hilang begitu halaman dimuat ulang.
        $this->get(route('dashboard.it.password-resets.index'))
            ->assertOk()
            ->assertDontSee('data-temporary-password', false)
            ->assertDontSee('sudah direset')
            ->assertDontSee($temporary);

        $log = AuditLog::where('action', 'Password direset')->firstOrFail();
        $this->assertStringContainsString('Aldora', $log->detail);
        $this->assertStringContainsString('Arga', $log->detail);
        $this->assertStringNotContainsString($temporary, $log->detail);
    }

    public function test_reset_signs_the_account_out_of_every_existing_session(): void
    {
        $aldora = $this->p['aldora'];
        foreach (['sesi-a', 'sesi-b'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $aldora->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'uji', 'payload' => 'x', 'last_activity' => time()]);
        }
        DB::table('sessions')->insert(['id' => 'sesi-orang-lain', 'user_id' => $this->p['gepeng']->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'uji', 'payload' => 'x', 'last_activity' => time()]);

        $this->reset($this->dev, $aldora);

        $this->assertSame(0, DB::table('sessions')->where('user_id', $aldora->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $this->p['gepeng']->id)->count());
    }

    public function test_an_account_cannot_reset_itself(): void
    {
        $before = $this->dev->password;

        $this->reset($this->dev, $this->dev)->assertSessionHas('error');

        $this->assertSame($before, $this->dev->fresh()->password);
        $this->assertFalse($this->dev->fresh()->must_change_password);
        $this->assertSame(0, AuditLog::where('action', 'Password direset')->count());
    }

    public function test_only_owners_can_reset_owner_accounts_and_only_owners_or_developers_can_reset_developers(): void
    {
        $itManager = $this->makeUser('hrd', ['it' => 'manage'], null, ['email' => 'it.hrd@wsm.local']);
        $dev2 = $this->makeDeveloper($this->p['owner'], ['email' => 'dev2@wsm.local']);

        // Developer dan pemegang IT biasa tidak boleh menyentuh Owner.
        $this->reset($this->dev, $this->p['owner'])->assertForbidden();
        $this->reset($itManager, $this->p['owner'])->assertForbidden();
        $this->assertFalse($this->p['owner']->fresh()->must_change_password);

        // Pemegang IT biasa tidak boleh mereset Developer; Developer lain dan Owner boleh.
        $this->reset($itManager, $dev2)->assertForbidden();
        $this->reset($this->dev, $dev2)->assertRedirect();
        $this->assertTrue($dev2->fresh()->must_change_password);
        $this->reset($this->p['owner'], $this->dev)->assertRedirect();
        $this->assertTrue($this->dev->fresh()->must_change_password);

        // Pemegang IT biasa boleh mereset karyawan biasa; Owner boleh mereset Owner lain.
        $this->reset($itManager, $this->p['gepeng'])->assertRedirect();
        $this->assertTrue($this->p['gepeng']->fresh()->must_change_password);
        $owner2 = $this->makeUser('owner', [], null, ['email' => 'owner2@wsm.local']);
        $this->reset($this->p['owner'], $owner2)->assertRedirect();
        $this->assertTrue($owner2->fresh()->must_change_password);
    }

    public function test_the_list_hides_the_reset_button_for_accounts_the_user_may_not_reset(): void
    {
        $this->actingAs($this->dev)
            ->get(route('dashboard.it.password-resets.index'))
            ->assertOk()
            ->assertSee(route('dashboard.it.password-resets.reset', $this->p['aldora']), false)
            ->assertDontSee(route('dashboard.it.password-resets.reset', $this->p['owner']), false)
            ->assertDontSee(route('dashboard.it.password-resets.reset', $this->dev), false);
    }

    public function test_the_list_can_be_searched(): void
    {
        $this->actingAs($this->dev)
            ->get(route('dashboard.it.password-resets.index', ['q' => 'gepeng']))
            ->assertOk()
            ->assertViewHas('employees', fn($e) => $e->total() === 1 && $e->first()->is($this->p['gepeng']));
    }

    public function test_reset_is_throttled_at_ten_per_minute(): void
    {
        $this->actingAs($this->p['owner']);

        for ($i = 0; $i < 10; $i++) {
            $this->post(route('dashboard.it.password-resets.reset', $this->p['aldora']))->assertRedirect();
        }

        $this->post(route('dashboard.it.password-resets.reset', $this->p['aldora']))->assertStatus(429);
    }

    // ---- wajib ganti password -------------------------------------------

    private function flagged(array $attributes = []): User
    {
        return $this->makeUser('karyawan', ['work' => 'manage'], $this->p['manajer']->id, array_merge([
            'email' => 'sementara@wsm.local',
            'must_change_password' => true,
        ], $attributes));
    }

    public function test_a_flagged_account_is_redirected_to_the_profile_from_every_page(): void
    {
        $user = $this->flagged();
        $this->actingAs($user);

        foreach (['employee.home', 'employee.leave.index', 'dashboard.index', 'dashboard.work.tracker.index'] as $name) {
            $this->get(route($name))
                ->assertRedirect(route('employee.profile.index'))
                ->assertSessionHas('warning');
        }

        $this->post(route('employee.attendance.clockIn'))->assertRedirect(route('employee.profile.index'));
    }

    public function test_a_flagged_account_can_still_open_the_profile_and_log_out(): void
    {
        $this->actingAs($this->flagged())
            ->get(route('employee.profile.index'))
            ->assertOk()
            ->assertSee('Ganti password dulu');

        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_json_requests_from_a_flagged_account_get_403(): void
    {
        $this->actingAs($this->flagged())->getJson(route('employee.home'))->assertForbidden();
    }

    public function test_an_unflagged_account_and_guests_are_unaffected(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('employee.home'))->assertOk();
        $this->post('/logout');

        $this->get(route('public.home'))->assertOk();
        $this->get(route('login'))->assertOk();
    }

    public function test_full_flow_reset_then_login_with_the_temporary_password_then_choose_a_new_one(): void
    {
        $this->reset($this->dev, $this->p['aldora']);
        $temporary = session('reset_result.password');
        $this->post('/logout');

        $this->post('/login', ['email' => 'aldora@wsm.local', 'password' => 'password'])->assertSessionHasErrors('email');

        $this->post('/login', ['email' => 'aldora@wsm.local', 'password' => $temporary])->assertRedirect();
        $this->get(route('employee.home'))->assertRedirect(route('employee.profile.index'));

        // Password baru sama dengan yang sementara: ditolak, masih terkunci.
        $this->patch(route('employee.profile.password'), ['current_password' => $temporary, 'password' => $temporary, 'password_confirmation' => $temporary])
            ->assertSessionHasErrors('password');
        $this->assertTrue($this->p['aldora']->fresh()->must_change_password);

        // Password saat ini salah: ditolak.
        $this->patch(route('employee.profile.password'), ['current_password' => 'salahsalah', 'password' => 'PasswordBaru99', 'password_confirmation' => 'PasswordBaru99'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue($this->p['aldora']->fresh()->must_change_password);

        // Benar: flag padam dan aplikasi terbuka lagi.
        $this->patch(route('employee.profile.password'), ['current_password' => $temporary, 'password' => 'PasswordBaru99', 'password_confirmation' => 'PasswordBaru99'])
            ->assertSessionHasNoErrors();

        $aldora = $this->p['aldora']->fresh();
        $this->assertFalse($aldora->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaru99', $aldora->password));
        $this->get(route('employee.home'))->assertOk();
    }

    public function test_a_normal_password_change_still_requires_a_different_password(): void
    {
        $this->actingAs($this->p['aldora'])
            ->patch(route('employee.profile.password'), ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrors('password');
    }

    // ---- TestingAccountsSeeder -------------------------------------------

    public function test_testing_accounts_seeder_creates_ancha_and_arga_with_the_documented_setup(): void
    {
        DB::table('users')->delete();
        DB::table('dashboard_access')->delete();
        $this->seed(OfficeSettingSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->seed(TestingAccountsSeeder::class);

        $owner = User::where('email', 'owner@wsm.local')->firstOrFail();
        $ancha = User::where('email', 'ancha@wsm.test')->firstOrFail();
        $arga = User::where('email', 'arga@wsm.test')->firstOrFail();

        $this->assertSame('manajer', $ancha->role);
        $this->assertSame('developer', $arga->role);
        $this->assertTrue(Hash::check('password', $ancha->password));
        $this->assertTrue(Hash::check('password', $arga->password));
        $this->assertFalse($arga->must_change_password);

        foreach ([$ancha, $arga] as $user) {
            $levels = $user->dashboardAccess()->pluck('level', 'module')->all();
            $this->assertCount(10, $levels);
            $this->assertSame(['manage'], array_values(array_unique($levels)));
        }

        // Struktur: Ancha di bawah Owner; semua akun non-Owner lain di bawah Ancha.
        $this->assertSame($owner->id, $ancha->manager_id);
        $this->assertSame($ancha->id, $arga->manager_id);
        $this->assertSame(
            $ancha->id,
            User::where('email', 'kanaya@wsm.local')->firstOrFail()->manager_id,
            'Akun yang tadinya langsung di bawah Owner pindah ke bawah Ancha.',
        );

        foreach (User::where('id', '!=', $owner->id)->where('id', '!=', $ancha->id)->get() as $user) {
            $top = $user;
            while ($top->manager_id !== null && $top->manager_id !== $ancha->id) {
                $top = User::findOrFail($top->manager_id);
            }

            $this->assertSame($ancha->id, $top->manager_id, "{$user->email} harus berada di bawah Ancha.");
        }

        // Rekap Absensi Ancha mencakup semua orang (dirinya + seluruh bawahan turunan).
        $ids = $this->actingAs($ancha)->get(route('attendance.recap.index'))->assertOk()->viewData('rows')->pluck('user.id')->sort()->values()->all();
        $this->assertSame(User::where('id', '!=', $owner->id)->pluck('id')->sort()->values()->all(), $ids);
    }

    public function test_testing_accounts_seeder_can_run_twice_and_needs_an_owner(): void
    {
        $this->seed(TestingAccountsSeeder::class);
        $this->seed(TestingAccountsSeeder::class);

        $this->assertSame(1, User::where('email', 'ancha@wsm.test')->count());
        $this->assertSame(1, User::where('email', 'arga@wsm.test')->count());
        $this->assertSame(20, DB::table('dashboard_access')->whereIn('user_id', User::whereIn('email', ['ancha@wsm.test', 'arga@wsm.test'])->pluck('id'))->count());

        // Tanpa Owner: tidak membuat apa pun.
        DB::table('users')->delete();
        DB::table('dashboard_access')->delete();
        $this->seed(TestingAccountsSeeder::class);
        $this->assertSame(0, User::withTrashed()->count());
    }
}