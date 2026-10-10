<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian A — halaman publik (tanpa login).
 * A1 halaman statis · A2 daftar karir · A3 detail lowongan · A4 form lamar ·
 * A5 form kontak · A6 halaman 404 · A7 halaman terproteksi → /login.
 */
class PublicPagesTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    private function opening(array $attributes = []): JobOpening
    {
        return JobOpening::create(array_merge([
            'title' => 'Social Media Specialist',
            'slug' => 'social-media-specialist',
            'division' => 'Marketing',
            'employment_type' => 'full_time',
            'description' => 'Mengelola akun sosial media WSM.',
            'requirements' => 'Paham konten musik.',
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
    }

    // ---- A1 -------------------------------------------------------------

    public function test_static_public_pages_open_without_login(): void
    {
        foreach (['/', '/tentang-kami', '/layanan', '/karir', '/kontak'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_home_shows_artist_profile_album_and_selected_releases(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Pionir Indonesian Bounce')
            ->assertSee('Map of Feelings')
            ->assertSee('Ari Lesmana')
            ->assertSee('Tomorrowland Belgium')
            ->assertSee('Rilisan pilihan')
            ->assertSee('Aku Harus Pergi')
            ->assertSee('https://mapoffeelings.com/', false)
            ->assertSee('593', false)
            ->assertSee('3.3', false);
    }

    public function test_home_keeps_owner_editable_headline_and_cards(): void
    {
        $setting = \App\Models\OfficeSetting::current();
        $setting->landing_content = [
            'headline' => 'Judul buatan Owner',
            'tagline' => 'Tagline buatan Owner',
            'cards' => [['label' => 'Label A', 'title' => 'Judul A', 'color' => '#112233']],
        ];
        $setting->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('Judul buatan Owner')
            ->assertSee('Tagline buatan Owner')
            ->assertSee('Judul A');
    }

    public function test_home_motion_hooks_are_present_and_no_remote_images_are_hotlinked(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Hook animasi dipakai di markup, dan layout publik memasang penanda `js`.
        $this->assertStringContainsString('data-reveal', $html);
        $this->assertStringContainsString('data-countup', $html);
        $this->assertStringContainsString("classList.add('js')", $html);

        // Tanpa gambar yang ditautkan langsung dari situs lain.
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_home_content_config_has_valid_https_links_and_stats(): void
    {
        $site = config('public_site');

        $links = array_merge(
            [$site['artist']['official_site'], $site['album']['url']],
            array_column($site['releases'], 'url'),
            array_column($site['socials'], 'url'),
        );

        foreach ($links as $url) {
            $this->assertStringStartsWith('https://', $url);
        }

        foreach ($site['stats'] as $stat) {
            $this->assertIsNumeric($stat['value']);
            $this->assertContains($stat['decimals'], [0, 1, 2]);
        }

        $this->assertNotEmpty($site['release_colors']);
    }

    public function test_about_and_services_pages_are_filled_without_placeholder_text(): void
    {
        $about = $this->get('/tentang-kami')->assertOk();
        $about->assertSee('Visi')
            ->assertSee('Misi')
            ->assertSee('Perjalanan Kami')
            ->assertSee('Tomorrowland Belgium')
            ->assertSee('2012');
        $this->assertStringNotContainsString('laceholder', $about->getContent());

        $services = $this->get('/layanan')->assertOk();
        $services->assertSee('Produksi Musik')
            ->assertSee('Kampanye & Promosi')
            ->assertSee('Arahan Kreatif')
            ->assertSee('Manajemen Tim')
            ->assertSee('Roblox');
        $this->assertStringNotContainsString('laceholder', $services->getContent());
    }

    public function test_home_work_section_is_filled_from_config(): void
    {
        foreach (config('public_site.work') as $item) {
            $this->get('/')->assertSee($item['title'])->assertSee($item['text']);
        }
    }

    public function test_public_pages_have_named_routes(): void
    {
        $this->assertSame(url('/'), route('public.home'));
        $this->assertSame(url('/tentang-kami'), route('public.about'));
        $this->assertSame(url('/layanan'), route('public.services'));
        $this->assertSame(url('/karir'), route('public.careers'));
        $this->assertSame(url('/kontak'), route('public.contact'));
    }

    // ---- A2 / A3 --------------------------------------------------------

    public function test_careers_list_shows_only_published_openings(): void
    {
        $this->opening(['title' => 'Lowongan Tayang', 'slug' => 'lowongan-tayang']);
        $this->opening(['title' => 'Lowongan Draft Rahasia', 'slug' => 'draft', 'status' => 'draft', 'published_at' => null]);
        $this->opening(['title' => 'Lowongan Sudah Ditutup', 'slug' => 'ditutup', 'status' => 'closed', 'closed_at' => now()]);

        $this->get('/karir')
            ->assertOk()
            ->assertSee('Lowongan Tayang')
            ->assertDontSee('Lowongan Draft Rahasia')
            ->assertDontSee('Lowongan Sudah Ditutup');
    }

    public function test_published_opening_detail_is_visible_and_draft_or_closed_return_404(): void
    {
        $this->opening();
        $this->opening(['slug' => 'draft', 'title' => 'Draft', 'status' => 'draft']);
        $this->opening(['slug' => 'ditutup', 'title' => 'Ditutup', 'status' => 'closed']);

        $this->get('/karir/social-media-specialist')
            ->assertOk()
            ->assertSee('Social Media Specialist')
            ->assertSee('Mengelola akun sosial media WSM.');

        $this->get('/karir/draft')->assertNotFound();
        $this->get('/karir/ditutup')->assertNotFound();
        $this->get('/karir/tidak-ada-sama-sekali')->assertNotFound();
    }

    // ---- A4 -------------------------------------------------------------

    public function test_job_application_requires_name_and_email(): void
    {
        $this->opening();

        $this->post('/karir/social-media-specialist/lamar', [])
            ->assertSessionHasErrors(['name', 'email']);

        $this->post('/karir/social-media-specialist/lamar', ['name' => 'Budi', 'email' => 'bukan-email'])
            ->assertSessionHasErrors(['email']);

        $this->assertSame(0, JobApplication::count());
    }

    public function test_valid_job_application_is_saved_with_status_baru(): void
    {
        $opening = $this->opening();

        $this->post('/karir/social-media-specialist/lamar', [
            'name' => 'Budi Pelamar',
            'email' => 'budi@example.com',
            'phone' => '08123456789',
            'message' => 'Saya tertarik.',
        ])->assertSessionHas('status')->assertRedirect()->assertSessionHasNoErrors();

        $application = JobApplication::firstOrFail();
        $this->assertSame($opening->id, $application->job_opening_id);
        $this->assertSame('Budi Pelamar', $application->name);
        $this->assertSame('baru', $application->status);
    }

    public function test_cannot_apply_to_a_draft_or_closed_opening(): void
    {
        $this->opening(['slug' => 'draft', 'status' => 'draft']);
        $this->opening(['slug' => 'ditutup', 'status' => 'closed']);

        foreach (['draft', 'ditutup'] as $slug) {
            $this->post("/karir/{$slug}/lamar", ['name' => 'Budi', 'email' => 'budi@example.com'])->assertNotFound();
        }

        $this->assertSame(0, JobApplication::count());
    }

    public function test_job_application_is_throttled_after_five_requests_per_minute(): void
    {
        $this->opening();
        $payload = ['name' => 'Budi', 'email' => 'budi@example.com'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/karir/social-media-specialist/lamar', $payload)->assertRedirect();
        }

        $this->post('/karir/social-media-specialist/lamar', $payload)->assertStatus(429);
        $this->assertSame(5, JobApplication::count());
    }

    // ---- A5 -------------------------------------------------------------

    public function test_contact_form_validates_input(): void
    {
        $this->post('/kontak', [])->assertSessionHasErrors(['name', 'email', 'message']);
        $this->post('/kontak', ['name' => 'A', 'email' => 'salah', 'message' => 'x'])->assertSessionHasErrors(['email']);
        $this->post('/kontak', ['name' => 'A', 'email' => 'a@example.com', 'message' => str_repeat('x', 2001)])
            ->assertSessionHasErrors(['message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_valid_contact_message_is_stored_as_unread(): void
    {
        $this->post('/kontak', [
            'name' => 'Sari',
            'email' => 'sari@example.com',
            'message' => 'Halo, mau tanya layanan.',
        ])->assertSessionHas('status')->assertRedirect()->assertSessionHasNoErrors();

        $message = ContactMessage::firstOrFail();
        $this->assertSame('baru', $message->status);
        $this->assertNull($message->read_at);
        $this->assertSame('Sari', $message->name);
    }

    public function test_contact_form_is_throttled_after_five_requests_per_minute(): void
    {
        $payload = ['name' => 'Sari', 'email' => 'sari@example.com', 'message' => 'Halo'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/kontak', $payload)->assertRedirect();
        }

        $this->post('/kontak', $payload)->assertStatus(429);
        $this->assertSame(5, ContactMessage::count());
    }

    // ---- A6 / A7 --------------------------------------------------------

    public function test_unknown_url_renders_the_custom_404_page(): void
    {
        $this->get('/abc')
            ->assertNotFound()
            ->assertSee('Halaman yang kamu cari tidak ada');
    }

    public function test_protected_areas_redirect_guests_to_login(): void
    {
        foreach (['/dashboard', '/app/home', '/owner/dashboard', '/absensi', '/persetujuan', '/rekrutmen/lowongan', '/dashboard/export-import'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }
}