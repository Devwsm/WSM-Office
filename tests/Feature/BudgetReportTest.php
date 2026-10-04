<?php

namespace Tests\Feature;

use App\Imports\ProjectBudgetImport;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\RoyaltyEntry;
use App\Models\User;
use App\Support\BudgetReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Project Budgeting — ringkasan, grafik Budget vs Actual, baris dengan
 * lagu & bukti bayar, PDF laporan, dan kolom baru di Export/Import Excel.
 * Kanaya (manajer) = budget manage; Rania (hrd) = tanpa akses budget.
 */
class BudgetReportTest extends TestCase
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

    private function project(string $name): Project
    {
        return Project::create(['name' => $name, 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
    }

    private function line(Project $project, string $category, string $item, float $budget, float $actual, array $extra = []): ProjectBudget
    {
        return ProjectBudget::create(['project_id' => $project->id, 'category' => $category, 'item' => $item, 'budget' => $budget, 'actual' => $actual] + $extra);
    }

    /** Dua project: MAP OF FEELINGS (hemat + over) dan Mavnus (sisa banyak). */
    private function seedLines(): array
    {
        $mof = $this->project('Map of Feelings');
        $mavnus = $this->project('Mavnus');

        $this->line($mof, 'Marketing', 'Ads', 20000000, 25000000, ['song_title' => 'Aku Harus Pergi']);
        $this->line($mof, 'marketing ', 'Influencer', 5000000, 1000000, ['song_title' => 'aku harus pergi']);
        $this->line($mof, 'Creative', 'Konten', 15000000, 8500000);
        $this->line($mavnus, 'Website', 'Hosting dan Domain', 5000000, 2000000);

        return [$mof, $mavnus];
    }

    // ---- ringkasan ------------------------------------------------------

    public function test_summary_totals_and_per_project_groups_on_the_page(): void
    {
        $this->seedLines();

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk();

        $totals = $response->viewData('totals');
        $this->assertEquals(45000000, $totals['budget']);
        $this->assertEquals(36500000, $totals['actual']);
        $this->assertEquals(8500000, $totals['remaining']);
        $this->assertSame(81, $totals['utilization']);

        // "Total Budget" diganti nama jadi "Budget Allocation" (2026-10-04).
        $response->assertSeeInOrder(['Budget Allocation', 'Rp 45.000.000', 'Actual', 'Rp 36.500.000', 'Remaining', 'Rp 8.500.000', 'Utilization', '81%']);
        $response->assertDontSee('>Total Budget<', false); // label kartu sudah diganti (panduan halaman masih menyebut nama lama)

        // Project diurutkan nama; kartu per project berisi kategori -> item.
        $cards = $response->viewData('cards');
        $this->assertSame(['Map of Feelings', 'Mavnus'], $cards->pluck('project.name')->all());
        $this->assertSame(3, $cards->first()['item_count']);
        $this->assertSame(['Marketing', 'Creative'], $cards->first()['sections']->pluck('name')->all());
        $this->assertCount(2, $cards->first()['sections']->first()['rows'], 'Marketing + "marketing " = satu kategori.');
        $this->assertEquals(5500000, $cards->first()['remaining']);
    }

    public function test_empty_state_has_no_summary_chart_or_pdf_button(): void
    {
        // Belum ada project sama sekali -> arahkan membuat project; tidak ada grafik/PDF.
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee('Belum ada project.')
            ->assertDontSee('id="budget-chart-title"', false)
            ->assertDontSee(route('dashboard.budget.pdf'), false);

        // Ada project tapi belum ada item -> kartu project tetap muncul (supaya Project Budget bisa diisi).
        $this->project('Map of Feelings');

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee('Map of Feelings')
            ->assertSee('Project ini belum punya kategori / item budget.')
            ->assertDontSee('id="budget-chart-title"', false)
            ->assertDontSee(route('dashboard.budget.pdf'), false);
    }

    public function test_project_filter_limits_totals_and_is_carried_to_links(): void
    {
        [, $mavnus] = $this->seedLines();

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', ['project_id' => $mavnus->id]))->assertOk();

        $this->assertEquals(5000000, $response->viewData('totals')['budget']);
        $this->assertSame(['Mavnus'], $response->viewData('cards')->pluck('project.name')->all());
        $response->assertSee('project_id=' . $mavnus->id . '&amp;group=song', false); // toggle grafik
        $response->assertSee('budget/pdf?project_id=' . $mavnus->id, false);
    }

    public function test_utilization_is_null_when_there_is_no_budget_to_compare(): void
    {
        $project = $this->project('Tanpa Anggaran');
        $this->line($project, 'Lain', 'Biaya tak terduga', 0, 750000);

        $report = BudgetReport::forProject(null);

        $this->assertNull($report->totals()['utilization']);
        $this->assertEquals(-750000, $report->totals()['remaining']);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee('Belum ada budget')
            ->assertSee('Melebihi budget');
    }

    // ---- grafik ---------------------------------------------------------

    public function test_chart_by_category_merges_spelling_variants_and_flags_overspend(): void
    {
        $this->seedLines();

        $chart = BudgetReport::forProject(null)->chart('category');

        // "Marketing" + "marketing " → 1 batang, ejaan pertama dipakai; urut budget terbesar dulu.
        $this->assertSame(['Marketing', 'Creative', 'Website'], $chart->pluck('label')->all());

        $marketing = $chart->firstWhere('label', 'Marketing');
        $this->assertEquals(25000000, $marketing['budget']);
        $this->assertEquals(26000000, $marketing['actual']);
        $this->assertTrue($marketing['over']);
        $this->assertSame(104, $marketing['utilization']);
        $this->assertEquals(100.0, $marketing['actual_pct'], 'Nilai terbesar = lebar penuh.');

        $this->assertFalse($chart->firstWhere('label', 'Creative')['over']);
    }

    public function test_chart_by_project_and_by_song(): void
    {
        $this->seedLines();
        $report = BudgetReport::forProject(null);

        $byProject = $report->chart('project');
        $this->assertSame(['Map of Feelings', 'Mavnus'], $byProject->pluck('label')->all());
        $this->assertEquals(40000000, $byProject->first()['budget']);

        // Lagu tidak membedakan huruf besar/kecil; baris tanpa lagu selalu paling bawah.
        $bySong = $report->chart('song');
        $this->assertSame(['Aku Harus Pergi', BudgetReport::NO_SONG_LABEL], $bySong->pluck('label')->all());
        $this->assertEquals(25000000, $bySong->first()['budget']);
        $this->assertEquals(20000000, $bySong->last()['budget']);

        // Mode tidak dikenal jatuh ke kategori, bukan error.
        $this->assertSame('category', BudgetReport::normalizeGroup('ngawur'));
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', ['group' => 'ngawur']))->assertOk()->assertViewHas('group', 'category');
    }

    public function test_chart_text_conveys_overspend_not_only_color(): void
    {
        $this->seedLines();

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee('Budget vs Actual')
            ->assertSee('Melebihi budget')
            ->assertSee('104%');
    }

    // ---- form: lagu, bukti bayar, saran ---------------------------------

    private function payload(Project $project, array $o = []): array
    {
        return array_merge(['project_id' => $project->id, 'category' => 'Marketing', 'item' => 'Ads', 'budget' => 1000000], $o);
    }

    public function test_store_saves_song_and_proof_and_adds_https_to_a_bare_link(): void
    {
        $project = $this->project('Map of Feelings');
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->post(route('dashboard.budget.store'), $this->payload($project, ['song_title' => 'Aku Harus Pergi', 'proof_link' => 'drive.google.com/file/d/abc/view']))
            ->assertRedirect(route('dashboard.budget.index'));

        $row = ProjectBudget::firstOrFail();
        $this->assertSame('Aku Harus Pergi', $row->song_title);
        $this->assertSame('https://drive.google.com/file/d/abc/view', $row->proof_link);

        // Kosong tetap null, link yang sudah punya skema tidak diubah.
        $kanaya->post(route('dashboard.budget.store'), $this->payload($project, ['item' => 'Tanpa bukti', 'song_title' => '', 'proof_link' => '']))->assertRedirect();
        $kanaya->post(route('dashboard.budget.store'), $this->payload($project, ['item' => 'Http', 'proof_link' => 'http://example.com/bukti']))->assertRedirect();

        $this->assertNull(ProjectBudget::where('item', 'Tanpa bukti')->value('song_title'));
        $this->assertNull(ProjectBudget::where('item', 'Tanpa bukti')->value('proof_link'));
        $this->assertSame('http://example.com/bukti', ProjectBudget::where('item', 'Http')->value('proof_link'));
    }

    public function test_proof_link_rejects_non_http_schemes(): void
    {
        $project = $this->project('Map of Feelings');
        $kanaya = $this->actingAs($this->p['manajer']);

        foreach (['javascript:alert(1)', 'ftp://example.com/x', 'data:text/html;base64,AAAA', 'bukan url spasi'] as $bad) {
            $kanaya->post(route('dashboard.budget.store'), $this->payload($project, ['proof_link' => $bad]))
                ->assertSessionHasErrors('proof_link');
        }

        $kanaya->post(route('dashboard.budget.store'), $this->payload($project, ['song_title' => str_repeat('a', 151)]))->assertSessionHasErrors('song_title');

        $this->assertDatabaseCount('project_budgets', 0);
    }

    public function test_update_can_set_and_clear_song_and_proof(): void
    {
        $project = $this->project('Map of Feelings');
        $row = $this->line($project, 'Marketing', 'Ads', 1000000, 0, ['song_title' => 'Lama', 'proof_link' => 'https://drive.google.com/x']);

        $this->actingAs($this->p['manajer'])
            ->patch(route('dashboard.budget.update', $row), $this->payload($project, ['song_title' => '', 'proof_link' => '']))
            ->assertRedirect();

        $this->assertNull($row->fresh()->song_title);
        $this->assertNull($row->fresh()->proof_link);
    }

    public function test_row_shows_song_and_a_safe_proof_link(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 1000000, 500000, ['song_title' => 'Aku Harus Pergi', 'proof_link' => 'https://drive.google.com/file/d/abc']);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee('Aku Harus Pergi')
            ->assertSee('href="https://drive.google.com/file/d/abc"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_form_suggestions_dedupe_by_case_and_include_royalty_titles(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 1, 0, ['song_title' => 'Aku Harus Pergi']);
        RoyaltyEntry::create(['title' => 'aku harus pergi', 'period' => '2026-08', 'status' => 'Reported', 'gross' => 1, 'share_pct' => 50]);
        RoyaltyEntry::create(['title' => 'Single Hujan', 'period' => '2026-08', 'status' => 'Reported', 'gross' => 1, 'share_pct' => 50]);

        $categories = BudgetReport::categorySuggestions();
        $this->assertContains('Marketing', $categories, 'Ejaan yang sudah dipakai tim menang.');
        $this->assertNotContains('MARKETING', $categories);
        $this->assertContains('SONG', $categories, 'Section standar Work Tracker tetap jadi saran.');

        $songs = BudgetReport::songSuggestions();
        $this->assertSame(['Aku Harus Pergi', 'Single Hujan'], $songs);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.create'))
            ->assertOk()
            ->assertSee('budget-category-options')
            ->assertSee('<option value="Single Hujan">', false);
    }

    // ---- akses ----------------------------------------------------------

    public function test_view_only_user_sees_report_but_no_manage_controls(): void
    {
        $this->seedLines();
        $viewer = $this->makeUser('karyawan', ['budget' => 'view'], $this->p['manajer']->id, ['name' => 'Viewer', 'email' => 'viewer@wsm.local']);

        $this->actingAs($viewer)->get(route('dashboard.budget.index'))
            ->assertOk()
            ->assertSee(route('dashboard.budget.pdf', ['group' => 'category']), false)
            ->assertSee(route('dashboard.export-import.preview', ['key' => 'budget', 'format' => 'excel']), false)
            ->assertDontSee(route('dashboard.budget.create'), false)
            ->assertDontSee(route('dashboard.export-import.import.show', ['key' => 'budget']), false)
            ->assertDontSee('>Hapus</button>', false);
    }

    // ---- PDF ------------------------------------------------------------

    public function test_pdf_report_streams_a_pdf_for_view_access(): void
    {
        $this->seedLines();
        $viewer = $this->makeUser('karyawan', ['budget' => 'view'], $this->p['manajer']->id, ['name' => 'Viewer', 'email' => 'viewer@wsm.local']);

        foreach ([$this->p['manajer'], $viewer] as $user) {
            $response = $this->actingAs($user)->get(route('dashboard.budget.pdf', ['group' => 'song']))->assertOk();

            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('laporan-anggaran-semua-project-2026-09-21.pdf', $response->headers->get('Content-Disposition'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_pdf_filename_follows_the_project_filter(): void
    {
        [$mof] = $this->seedLines();

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.pdf', ['project_id' => $mof->id]))->assertOk();

        $this->assertStringContainsString('laporan-anggaran-map-of-feelings-2026-09-21.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_pdf_report_is_denied_without_budget_access(): void
    {
        $this->seedLines();

        $this->actingAs($this->p['hrd'])->get(route('dashboard.budget.pdf'))->assertForbidden();
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.budget.pdf'))->assertForbidden();
    }

    // ---- Export / Import Excel -----------------------------------------

    private function cellsOf(TestResponse $response): array
    {
        $download = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $download);

        $sheet = IOFactory::load($download->getFile()->getPathname())->getActiveSheet();

        return collect($sheet->toArray(null, true, false, false))->flatten()->filter(fn($v) => $v !== null && $v !== '')->map(fn($v) => (string) $v)->values()->all();
    }

    private function csv(array $headings, array $rows): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headings);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);

        return UploadedFile::fake()->createWithContent('budget.csv', stream_get_contents($stream));
    }

    public function test_excel_export_includes_song_and_proof_columns(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 1000000, 0, ['song_title' => 'Aku Harus Pergi', 'proof_link' => 'https://drive.google.com/x']);

        $cells = $this->cellsOf($this->actingAs($this->p['owner'])->get(route('dashboard.export-import.download', ['key' => 'budget', 'format' => 'excel'])));

        foreach (['Lagu', 'Bukti Bayar', 'Aku Harus Pergi', 'https://drive.google.com/x'] as $expected) {
            $this->assertContains($expected, $cells);
        }
    }

    public function test_import_reads_song_and_proof_and_old_templates_still_work(): void
    {
        $this->project('Map of Feelings');
        $kanaya = $this->p['manajer'];

        // Template lama (6 kolom) — kolom baru boleh tidak ada.
        $old = $this->actingAs($kanaya)->post(route('dashboard.export-import.import.preview', ['key' => 'budget']), [
            'file' => $this->csv(['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan'], [['Map of Feelings', 'Creative', 'Konten lama', '1000', '0', '']]),
        ]);
        $this->actingAs($kanaya)->post(route('dashboard.export-import.import.commit', ['key' => 'budget']), ['token' => $old->viewData('token')])->assertRedirect();

        // Template baru.
        $new = $this->actingAs($kanaya)->post(route('dashboard.export-import.import.preview', ['key' => 'budget']), [
            'file' => $this->csv(['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan', 'lagu', 'bukti_bayar'], [
                ['Map of Feelings', 'Marketing', 'Ads', '2000', '500', '', 'Aku Harus Pergi', 'https://drive.google.com/x'],
                ['Map of Feelings', 'Marketing', 'Link jelek', '2000', '0', '', '', 'javascript:alert(1)'],
            ]),
        ]);
        $this->assertCount(1, $new->viewData('valid'));
        $this->assertCount(1, $new->viewData('invalid'));
        $this->actingAs($kanaya)->post(route('dashboard.export-import.import.commit', ['key' => 'budget']), ['token' => $new->viewData('token')])->assertRedirect();

        $this->assertDatabaseCount('project_budgets', 2);
        $ads = ProjectBudget::where('item', 'Ads')->firstOrFail();
        $this->assertSame('Aku Harus Pergi', $ads->song_title);
        $this->assertSame('https://drive.google.com/x', $ads->proof_link);

        $lama = ProjectBudget::where('item', 'Konten lama')->firstOrFail();
        $this->assertNull($lama->song_title);
        $this->assertNull($lama->proof_link);
    }

    public function test_import_rules_accept_blank_new_columns_and_reject_bad_links(): void
    {
        $import = new ProjectBudgetImport;
        $base = ['project' => 'X', 'kategori' => 'K', 'item' => 'I', 'anggaran' => '1', 'realisasi' => null, 'catatan' => null];
        $rules = $import->rules();
        unset($rules['project']); // 'exists:projects,name' butuh data; bukan fokus tes ini.
        $base['project'] = 'X';

        $this->assertTrue(Validator::make($base + ['lagu' => null, 'bukti_bayar' => null], $rules)->passes());
        $this->assertTrue(Validator::make($base + ['lagu' => 'Judul', 'bukti_bayar' => 'https://drive.google.com/a'], $rules)->passes());
        $this->assertFalse(Validator::make($base + ['lagu' => null, 'bukti_bayar' => 'drive.google.com/a'], $rules)->passes());
        $this->assertFalse(Validator::make($base + ['lagu' => null, 'bukti_bayar' => 'ftp://x.com/a'], $rules)->passes());
    }
}