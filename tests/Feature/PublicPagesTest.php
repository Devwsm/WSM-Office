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