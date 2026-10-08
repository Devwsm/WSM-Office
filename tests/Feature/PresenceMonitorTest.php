<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Presence;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Monitor Login (Dashboard > IT) — pelacakan halaman terakhir, heartbeat,
 * event login/logout, status Online/Idle/Offline, dan akses halamannya.
 */
class PresenceMonitorTest extends TestCase
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
        Storage::fake('local');
        $this->p = $this->company();
    }

    // --- Akses ---

    public function test_owner_dan_pemilik_akses_it_view_bisa_membuka_monitor_login(): void
    {
        $this->actingAs($this->p['owner'])->get(route('dashboard.it.presence.index'))
            ->assertOk()->assertSee('Monitor Login');

        $this->grant($this->p['gepeng'], 'it', 'view');
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.it.presence.index'))->assertOk();
    }

    public function test_karyawan_tanpa_akses_it_dan_tamu_ditolak(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('dashboard.it.presence.index'))->assertForbidden();

        Auth::logout();
        $this->get(route('dashboard.it.presence.index'))->assertRedirect(route('login'));
    }

    // --- Pelacakan halaman ---

    public function test_membuka_halaman_mencatat_route_dan_label_tanpa_menyentuh_updated_at(): void
    {
        $aldora = $this->p['aldora'];
        $updatedAt = $aldora->fresh()->updated_at->toDateTimeString();

        $this->actingAs($aldora)->get(route('employee.home'))->assertOk();

        $aldora->refresh();
        $this->assertSame('employee.home', $aldora->last_seen_route);
        $this->assertSame('App Mode · Home', $aldora->last_seen_label);
        $this->assertNotNull($aldora->last_seen_at);
        $this->assertSame($updatedAt, $aldora->updated_at->toDateTimeString());
    }

    public function test_halaman_dashboard_memakai_label_yang_ramah(): void
    {
        $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertOk();

        $this->assertSame('Executive People Overview', $this->p['owner']->fresh()->last_seen_label);
    }

    public function test_foto_ajax_dan_post_tidak_dianggap_membuka_halaman(): void
    {
        $aldora = $this->p['aldora'];

        $this->actingAs($aldora)->get(route('avatar.show', $aldora))->assertStatus(404);
        $this->actingAs($aldora)->get(route('employee.home'), ['X-Requested-With' => 'XMLHttpRequest']);

        $this->assertNull($aldora->fresh()->last_seen_at);
    }

    public function test_halaman_yang_sama_tidak_menulis_ulang_dalam_20_detik(): void
    {
        $aldora = $this->p['aldora'];
        $this->actingAs($aldora)->get(route('employee.home'));
        $first = $aldora->fresh()->last_seen_at;

        Carbon::setTestNow(Carbon::now()->addSeconds(10));
        $this->actingAs($aldora)->get(route('employee.home'));
        $this->assertTrue($first->equalTo($aldora->fresh()->last_seen_at));

        Carbon::setTestNow(Carbon::now()->addSeconds(30));
        $this->actingAs($aldora)->get(route('employee.home'));
        $this->assertTrue($aldora->fresh()->last_seen_at->greaterThan($first));
    }

    // --- Heartbeat ---

    public function test_heartbeat_memperbarui_waktu_tapi_tidak_mengubah_halaman_terakhir(): void
    {
        $aldora = $this->p['aldora'];
        $this->actingAs($aldora)->get(route('employee.leave.index'))->assertOk();
        $before = $aldora->fresh();

        Carbon::setTestNow(Carbon::now()->addMinutes(2));
        $this->actingAs($aldora)->post(route('presence.ping'))->assertNoContent();

        $after = $aldora->fresh();
        $this->assertTrue($after->last_seen_at->greaterThan($before->last_seen_at));
        $this->assertSame('employee.leave.index', $after->last_seen_route);
        $this->assertSame('App Mode · Izin / Cuti', $after->last_seen_label);
    }

    public function test_heartbeat_butuh_login(): void
    {
        $this->post(route('presence.ping'))->assertRedirect(route('login'));
    }

    public function test_layout_app_mode_dan_dashboard_memuat_script_heartbeat(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('employee.home'))->assertSee('presence\\/ping', false);
        $this->actingAs($this->p['owner'])->get(route('owner.dashboard'))->assertSee('presence\\/ping', false);
    }

    // --- Status ---

    public function test_status_online_idle_offline_mengikuti_umur_aktivitas(): void
    {
        $u = $this->p['aldora'];

        $this->assertSame(Presence::OFFLINE, Presence::status($u), 'belum pernah tercatat');

        $u->forceFill(['last_seen_at' => now()->subSeconds(60)]);
        $this->assertSame(Presence::ONLINE, Presence::status($u));

        $u->forceFill(['last_seen_at' => now()->subMinutes(5)]);
        $this->assertSame(Presence::IDLE, Presence::status($u));

        $u->forceFill(['last_seen_at' => now()->subMinutes(20)]);
        $this->assertSame(Presence::OFFLINE, Presence::status($u));
    }

    public function test_login_dan_logout_event_dicatat_dan_logout_langsung_offline(): void
    {
        $u = $this->p['aldora'];

        event(new Login('web', $u, false));
        $u->refresh();
        $this->assertNotNull($u->last_login_at);
        $this->assertSame(Presence::ONLINE, Presence::status($u));

        Carbon::setTestNow(Carbon::now()->addSeconds(5));
        event(new Logout('web', $u));
        $u->refresh();
        $this->assertNotNull($u->last_logout_at);
        $this->assertSame(Presence::OFFLINE, Presence::status($u));

        Carbon::setTestNow(Carbon::now()->addSeconds(5));
        event(new Login('web', $u, false));
        $this->assertSame(Presence::ONLINE, Presence::status($u->fresh()));
    }

    // --- Tampilan halaman ---

    public function test_halaman_menampilkan_status_halaman_aktif_dan_login_terakhir(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('employee.workTracker.calendar'))->assertOk();
        event(new Login('web', $this->p['aldora']->fresh(), false));

        $html = $this->actingAs($this->p['owner'])->get(route('dashboard.it.presence.index'))
            ->assertOk()->getContent();

        $this->assertStringContainsString($this->p['aldora']->name, $html);
        $this->assertStringContainsString('Sedang di', $html);
        $this->assertStringContainsString('App Mode · Timeline Calendar', $html);
        $this->assertStringContainsString('Login terakhir', $html);
        $this->assertStringContainsString('Belum ada data aktivitas', $html); // karyawan lain belum pernah tercatat
    }

    public function test_filter_status_dan_pencarian(): void
    {
        $this->p['aldora']->forceFill(['last_seen_at' => now()->subSeconds(30)])->saveQuietly();
        $owner = $this->p['owner'];

        $online = $this->actingAs($owner)->get(route('dashboard.it.presence.index', ['status' => 'online']))
            ->assertOk()->getContent();
        $this->assertStringContainsString($this->p['aldora']->name, $online);
        $this->assertStringNotContainsString($this->p['gepeng']->name, $online);

        $search = $this->actingAs($owner)->get(route('dashboard.it.presence.index', ['q' => $this->p['gepeng']->name]))
            ->assertOk()->getContent();
        $this->assertStringContainsString($this->p['gepeng']->name, $search);
        $this->assertStringNotContainsString($this->p['aldora']->name, $search);
    }

    public function test_permintaan_ajax_hanya_mengembalikan_bagian_daftar_tanpa_layout(): void
    {
        $html = $this->actingAs($this->p['owner'])
            ->get(route('dashboard.it.presence.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringContainsString('Online', $html);
        $this->assertStringContainsString('Diperbarui', $html);
    }

    public function test_tautan_monitor_login_ada_di_sidebar_untuk_pemilik_akses_it(): void
    {
        $this->actingAs($this->p['owner'])->get(route('dashboard.it.index'))
            ->assertOk()->assertSee(route('dashboard.it.presence.index'), false);
    }
}