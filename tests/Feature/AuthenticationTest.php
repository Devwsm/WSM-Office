<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian B — login, logout, sesi (B1–B9) dan
 * bagian C23 (ganti password), F13 (kunci dashboard).
 */
class AuthenticationTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    // ---- B1 / B2 --------------------------------------------------------

    public function test_login_page_is_shown_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_wrong_password_shows_error_message_and_stays_guest(): void
    {
        $user = $this->makeUser('karyawan');

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'salah-total'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_unknown_email_and_missing_fields_are_rejected(): void
    {
        $this->post('/login', ['email' => 'tidak-ada@wsm.local', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->post('/login', [])->assertSessionHasErrors(['email', 'password']);
        $this->post('/login', ['email' => 'bukan-email', 'password' => 'x'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_attempts_per_minute(): void
    {
        $user = $this->makeUser('karyawan');

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 diblokir walau passwordnya benar.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    // ---- B3 / B4 --------------------------------------------------------

    public function test_owner_lands_on_owner_dashboard_after_login(): void
    {
        $owner = $this->makeUser('owner');

        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])
            ->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_every_non_owner_role_lands_on_app_home_after_login(): void
    {
        foreach (['manajer', 'hrd', 'karyawan'] as $role) {
            $user = $this->makeUser($role);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('employee.home'));

            $this->post('/logout');
        }
    }

    public function test_seeded_style_accounts_can_all_log_in_with_default_password(): void
    {
        $company = $this->company();

        foreach ($company as $key => $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
            $this->assertTrue(Auth::check() && Auth::id() === $user->id, "Akun {$key} gagal login");
            $this->post('/logout');
        }
    }

    // ---- B5 / B6 --------------------------------------------------------

    public function test_after_login_user_returns_to_the_originally_requested_url(): void
    {
        $owner = $this->makeUser('owner');

        $this->get('/owner/pengaturan-kantor')->assertRedirect('/login');

        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])
            ->assertRedirect(url('/owner/pengaturan-kantor'));
    }

    public function test_logged_in_user_cannot_see_login_form_again(): void
    {
        $user = $this->makeUser('karyawan');

        $this->actingAs($user)->get('/login')->assertRedirect();
    }

    // ---- B7 -------------------------------------------------------------

    public function test_remember_me_sets_a_remember_cookie_only_when_checked(): void
    {
        $user = $this->makeUser('karyawan');

        $with = $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1']);
        $with->assertCookie(Auth::guard('web')->getRecallerName());
        $this->assertNotEmpty($user->fresh()->remember_token);

        $this->post('/logout');

        $other = $this->makeUser('karyawan');
        $without = $this->post('/login', ['email' => $other->email, 'password' => 'password']);
        $without->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    // ---- B8 -------------------------------------------------------------

    public function test_logout_returns_to_login_and_blocks_protected_pages(): void
    {
        $user = $this->makeUser('karyawan');

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get('/app/home')->assertRedirect('/login');
    }

    public function test_logout_requires_login(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }

    // ---- B9 -------------------------------------------------------------

    public function test_deactivated_account_cannot_log_in_and_can_after_reactivation(): void
    {
        $user = $this->makeUser('karyawan');
        $user->delete(); // soft delete = "nonaktif"

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->restore();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    // ---- C23: ganti password -------------------------------------------

    public function test_change_password_rejects_wrong_current_short_and_unconfirmed(): void
    {
        $user = $this->makeUser('karyawan');

        $this->actingAs($user)->patch('/app/profile/password', [
            'current_password' => 'salah',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->patch('/app/profile/password', [
            'current_password' => 'password',
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->patch('/app/profile/password', [
            'current_password' => 'password',
            'password' => 'passwordbaru',
            'password_confirmation' => 'beda-sekali',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password), 'Password tidak boleh berubah kalau validasi gagal.');
    }

    public function test_change_password_success_allows_login_with_new_password_only(): void
    {
        $user = $this->makeUser('karyawan');

        $this->actingAs($user)->patch('/app/profile/password', [
            'current_password' => 'password',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertSessionHas('status')->assertRedirect()->assertSessionHasNoErrors();

        $this->post('/logout');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $user->email, 'password' => 'passwordbaru123'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    // ---- F13: kunci dashboard ------------------------------------------

    public function test_locked_dashboard_redirects_to_lock_screen_and_returns_after_unlock(): void
    {
        $owner = $this->makeUser('owner');

        $this->actingAs($owner)->post('/dashboard-lock/kunci')->assertRedirect(route('employee.home'));

        $this->get('/dashboard')->assertRedirect(route('dashboard.lock.show'));
        $this->get('/dashboard-lock')->assertOk();

        // Password salah ditolak dan tetap terkunci.
        $this->post('/dashboard-lock/buka', ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->get('/dashboard')->assertRedirect(route('dashboard.lock.show'));

        // Password benar → kembali ke URL semula.
        $this->post('/dashboard-lock/buka', ['password' => 'password'])->assertRedirect(url('/dashboard'));
        $this->get('/dashboard')->assertOk();
    }

    public function test_lock_also_guards_owner_area_and_module_pages(): void
    {
        $company = $this->company();

        $this->actingAs($company['owner'])->post('/dashboard-lock/kunci');

        foreach (['/owner/dashboard', '/owner/employees', '/dashboard/kpi', '/absensi', '/persetujuan', '/rekrutmen/lowongan'] as $url) {
            $this->get($url)->assertRedirect(route('dashboard.lock.show'));
        }

        // App Mode (karyawan) bukan area manajemen, tetap terbuka.
        $this->get('/app/home')->assertOk();
    }

    public function test_employee_with_module_access_can_lock_the_dashboard_too(): void
    {
        $aldora = $this->company()['aldora'];

        $this->actingAs($aldora)->post('/dashboard-lock/kunci')->assertRedirect();
        $this->get('/dashboard/work')->assertRedirect(route('dashboard.lock.show'));

        $this->post('/dashboard-lock/buka', ['password' => 'password'])->assertRedirect(url('/dashboard/work'));
    }

    public function test_lock_screen_redirects_away_when_not_locked_and_cancel_goes_home(): void
    {
        $owner = $this->makeUser('owner');

        $this->actingAs($owner)->get('/dashboard-lock')->assertRedirect(route('dashboard.index'));

        $this->post('/dashboard-lock/kunci');
        $this->post('/dashboard-lock/batal')->assertRedirect(route('employee.home'));
    }

    public function test_unlock_screen_needs_login(): void
    {
        $this->get('/dashboard-lock')->assertRedirect('/login');
        $this->post('/dashboard-lock/buka', ['password' => 'x'])->assertRedirect('/login');
    }
}