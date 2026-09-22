<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Popup informasi preview (README Bab 4.2 no. 8, config/entry_popups.php,
 * App\Support\EntryPopups, partials/entry-popups.blade.php).
 *
 * `phpunit.xml` mematikan WOS_ENTRY_POPUPS secara default (biar 369 tes
 * lain gak perlu tahu soal popup ini) — tiap tes di sini menyalakannya
 * sendiri lewat Config::set('entry_popups.enabled', true).
 *
 * Yang TIDAK dites di sini (manual, lihat README Bab 4.2 no. 8): antrean
 * tampil + sessionStorage (resources/js/entry-popups.js — JS, bukan
 * respons server), animasi equalizer/vinyl, tampilan di HP.
 */
class EntryPopupsTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('entry_popups.enabled', true);

        $this->officeSetting();
    }

    public function test_popup_muncul_di_halaman_publik_dan_login(): void
    {
        $this->get(route('public.home'))
            ->assertOk()
            ->assertSee('Masih tahap uji coba', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masih tahap uji coba', false);
    }

    public function test_app_mode_menampilkan_sambutan_dan_peringatan_urut_welcome_dulu(): void
    {
        $aldora = $this->company()['aldora'];

        $html = $this->actingAs($aldora)->get(route('employee.home'))->assertOk()->getContent();

        $this->assertStringContainsString('Welcome to W.O.S', $html);
        $this->assertStringContainsString('Masih tahap uji coba', $html);

        // Data popup dikirim sebagai JSON ke x-data (Illuminate\Support\Js::from,
        // tanda kutip di-encode jadi \u0022) — Sambutan harus ada SEBELUM
        // Peringatan di array `popups` (urutan tampil).
        $welcomePos = strpos($html, 'key\u0022:\u0022welcome\u0022');
        $noticePos = strpos($html, 'key\u0022:\u0022notice-app\u0022');

        $this->assertIsInt($welcomePos, 'Popup welcome tidak ditemukan di data x-data.');
        $this->assertIsInt($noticePos, 'Popup notice-app tidak ditemukan di data x-data.');
        $this->assertLessThan($noticePos, $welcomePos, 'Sambutan harus muncul lebih dulu daripada Peringatan di App Mode.');
    }

    public function test_dashboard_menampilkan_peringatan_versi_dashboard_tanpa_sambutan(): void
    {
        $owner = $this->company()['owner'];

        $html = $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('dashboard ini', $html);
        $this->assertStringNotContainsString('Welcome to W.O.S', $html);
    }

    public function test_saklar_wos_entry_popups_mematikan_semua_popup(): void
    {
        Config::set('entry_popups.enabled', false);

        $this->get(route('public.home'))->assertOk()->assertDontSee('Masih tahap uji coba');

        $aldora = $this->company()['aldora'];
        $this->actingAs($aldora)->get(route('employee.home'))
            ->assertOk()
            ->assertDontSee('Masih tahap uji coba')
            ->assertDontSee('Welcome to W.O.S');
    }

    public function test_saklar_wos_preview_mode_mematikan_peringatan_tapi_sambutan_tetap_ada(): void
    {
        Config::set('entry_popups.preview_mode', false);

        $aldora = $this->company()['aldora'];
        $html = $this->actingAs($aldora)->get(route('employee.home'))->assertOk()->getContent();

        $this->assertStringContainsString('Welcome to W.O.S', $html);
        $this->assertStringNotContainsString('Masih tahap uji coba', $html);

        $this->get(route('public.home'))->assertOk()->assertDontSee('Masih tahap uji coba');
    }

    public function test_tidak_muncul_di_halaman_error(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertDontSee('Masih tahap uji coba');
    }

    public function test_tidak_muncul_di_layar_kunci_dashboard(): void
    {
        $owner = $this->company()['owner'];
        $this->actingAs($owner)->post(route('dashboard.lock.lock'));

        $this->get(route('owner.dashboard'))
            ->assertRedirect(route('dashboard.lock.show'));

        $this->get(route('dashboard.lock.show'))
            ->assertOk()
            ->assertDontSee('Masih tahap uji coba');
    }

    public function test_tidak_muncul_selama_wajib_ganti_password(): void
    {
        $user = $this->makeUser('karyawan', [], null, ['must_change_password' => true]);

        $this->actingAs($user)->get(route('employee.profile.index'))
            ->assertOk()
            ->assertDontSee('Masih tahap uji coba')
            ->assertDontSee('Welcome to W.O.S');
    }

    public function test_teks_popup_di_escape(): void
    {
        Config::set('entry_popups.notice.title', 'Aman <script>alert(1)</script>');

        $this->get(route('public.home'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Aman', false);
    }

    /** Smoke test: 5 akun demo (Owner, Manajer, HRD, dua Karyawan) tetap bisa buka App Mode & dashboard tanpa error walau popup aktif. */
    public function test_smoke_lima_akun_demo_tetap_bisa_buka_app_mode(): void
    {
        foreach ($this->company() as $user) {
            $this->actingAs($user)->get(route('employee.home'))->assertOk();
        }
    }
}