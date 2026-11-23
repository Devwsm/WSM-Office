<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Penjaga tampilan HP: tab bar IT, pagination bertema, dan aturan class yang
 * dulu menyebabkan masalah di layar kecil (tidak bisa dites lewat browser di CI).
 */
class ResponsiveShellTest extends TestCase
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

    // --- Pagination ---

    public function test_pagination_uses_the_wsm_theme_by_default(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 15), 60, 15, 2, ['path' => '/contoh']);

        // Spasi/baris baru dilipat seperti browser; pemformat Blade boleh memecah teks.
        $html = preg_replace('/\s+/', ' ', (string) $paginator->links());

        $this->assertStringContainsString('wsm-pagination', $html);
        $this->assertStringContainsString('Menampilkan 16–30 dari 60', $html);
        $this->assertStringContainsString('Hal 2 dari 4', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('rel="prev"', $html);
        $this->assertStringContainsString('rel="next"', $html);
        // Bukan tampilan Tailwind bawaan Laravel.
        $this->assertStringNotContainsString('Showing', $html);
        $this->assertStringNotContainsString('Previous', $html);
    }

    public function test_pagination_disables_the_ends_and_hides_itself_for_one_page(): void
    {
        $first = (string) (new LengthAwarePaginator(range(1, 15), 40, 15, 1, ['path' => '/x']))->links();
        $this->assertStringContainsString('aria-disabled="true"', $first);
        $this->assertStringNotContainsString('rel="prev"', $first);

        $last = (string) (new LengthAwarePaginator(range(1, 10), 40, 15, 3, ['path' => '/x']))->links();
        $this->assertStringNotContainsString('rel="next"', $last);

        $single = (string) (new LengthAwarePaginator(range(1, 5), 5, 15, 1, ['path' => '/x']))->links();
        $this->assertSame('', trim($single));
    }

    public function test_pagination_keeps_the_query_string_on_links(): void
    {
        $paginator = (new LengthAwarePaginator(range(1, 15), 60, 15, 1, ['path' => '/x']))
            ->appends(['q' => 'rania', 'status' => 'online']);

        $html = (string) $paginator->links();

        $this->assertStringContainsString('q=rania', $html);
        $this->assertStringContainsString('status=online', $html);
    }

    // --- Tab bar menu IT ---

    public function test_every_it_page_shares_the_wrapping_tab_bar_with_one_active_tab(): void
    {
        $pages = [
            'dashboard.it.index' => 'Audit Log',
            'dashboard.it.changelog.index' => 'System Changelog',
            'dashboard.it.presence.index' => 'Monitor Login',
            'dashboard.it.password-resets.index' => 'Reset Password',
        ];

        foreach ($pages as $route => $label) {
            $response = $this->actingAs($this->p['owner'])->get(route($route))->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('aria-label="Menu IT"', $html, $route);
            $this->assertMatchesRegularExpression('/<nav class="[^"]*flex-wrap[^"]*" aria-label="Menu IT"/', $html, $route);
            $this->assertSame(1, preg_match_all('/aria-current="page"[^>]*>' . preg_quote($label, '/') . '</', $html), $route);
            // Semua tab tujuan tampil untuk pemilik akses Manage.
            foreach (array_keys($pages) as $target) {
                $this->assertStringContainsString(route($target), $html, "$route -> $target");
            }
        }
    }

    public function test_reset_password_tab_is_hidden_without_manage_access(): void
    {
        $this->grant($this->p['gepeng'], 'it', 'view');

        $html = $this->actingAs($this->p['gepeng'])
            ->get(route('dashboard.it.presence.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aria-label="Menu IT"', $html);
        $this->assertStringNotContainsString(route('dashboard.it.password-resets.index'), $html);
    }

    public function test_monitor_login_search_and_rows_are_built_for_small_screens(): void
    {
        $html = $this->actingAs($this->p['owner'])
            ->get(route('dashboard.it.presence.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('min-w-0 flex-1', $html); // kolom cari melebar di HP
        $this->assertStringContainsString('w-full flex-none grid-cols-2', $html); // waktu online turun baris di HP
    }

    // --- Aturan class lintas halaman ---

    public function test_layouts_declare_viewport_fit_cover_so_safe_area_works(): void
    {
        foreach (['app', 'employee', 'public', 'error'] as $layout) {
            $src = File::get(resource_path("views/layouts/$layout.blade.php"));
            $this->assertStringContainsString('viewport-fit=cover', $src, $layout);
        }
        $this->assertStringContainsString('viewport-fit=cover', File::get(resource_path('views/auth/login.blade.php')));
        $this->assertStringContainsString('viewport-fit=cover', File::get(resource_path('views/dashboard/locked.blade.php')));
    }

    public function test_no_view_uses_viewport_units_that_break_on_mobile_browsers(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/class="([^"]*)"/', File::get($file->getPathname()), $m);
            foreach ($m[1] as $classes) {
                if (preg_match('/(?<![\w-])(h-screen|min-h-screen|max-h-screen)(?![\w-])|max-h-\[\d+vh\]/', $classes)) {
                    $offenders[] = $file->getRelativePathname() . ' => ' . $classes;
                }
            }
        }

        $this->assertSame([], $offenders, "Pakai dvh (h-dvh, min-h-dvh, max-h-[90dvh]) supaya tidak tertutup toolbar browser HP.");
    }

    public function test_form_controls_never_force_a_font_below_16px_on_phones(): void
    {
        $offenders = [];
        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $src = File::get($file->getPathname());
            preg_match_all('/<(?:input|textarea|select)\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/s', $src, $m);
            foreach ($m[0] as $tag) {
                if (preg_match('/(?<![:\w-])text-(?:xs|sm|\[\d+px\])!/', $tag)) {
                    $offenders[] = $file->getRelativePathname() . ' => ' . preg_replace('/\s+/', ' ', mb_substr($tag, 0, 120));
                }
            }
        }

        $this->assertSame([], $offenders, 'iOS memperbesar halaman kalau kolom isian < 16px. Pakai "text-base! sm:text-xs!".');
    }
}