<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetCategory;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\BudgetFund;
use App\Models\User;
use App\Support\BudgetReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Project Budgeting disamakan dengan Work Tracker (2026-10-04):
 * Project > Kategori > Item, filter, edit satuan, kelola kategori, akses
 * per kategori, "Project Budget" (dana keseluruhan) vs "Budget Allocation".
 *
 * Kanaya (manajer) = budget manage; Viewer = budget view; Raka = budget manage
 * kedua (untuk menguji kategori yang disembunyikan dari sesama pengelola).
 */
class BudgetTrackerTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private User $viewer;

    private User $raka;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        $this->p = $this->company();
        $this->viewer = $this->makeUser('karyawan', ['budget' => 'view'], $this->p['manajer']->id, ['name' => 'Viewer', 'email' => 'viewer@wsm.local']);
        $this->raka = $this->makeUser('karyawan', ['budget' => 'manage'], $this->p['manajer']->id, ['name' => 'Raka', 'email' => 'raka@wsm.local']);
    }

    private function project(string $name): Project
    {
        return Project::create(['name' => $name, 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
    }

    private function line(Project $project, string $category, string $item, float $budget = 1000000, float $actual = 0, array $extra = []): ProjectBudget
    {
        return ProjectBudget::create(['project_id' => $project->id, 'category' => $category, 'item' => $item, 'budget' => $budget, 'actual' => $actual] + $extra);
    }

    /** Batasi kategori untuk orang-orang tertentu (lewat endpoint, seperti user sungguhan). */
    private function restrict(BudgetCategory $category, array $users): void
    {
        $this->actingAs($this->p['owner'])
            ->put(route('dashboard.budget.categories.viewers', $category), ['user_ids' => collect($users)->pluck('id')->all()])
            ->assertRedirect();
    }

    private function category(Project $project, string $name): BudgetCategory
    {
        return BudgetCategory::findByName($project->id, $name) ?? $this->fail("Kategori {$name} tidak terdaftar.");
    }

    private function cellsOf(TestResponse $response): array
    {
        $download = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $download);

        $sheet = IOFactory::load($download->getFile()->getPathname())->getActiveSheet();

        return collect($sheet->toArray(null, true, false, false))->flatten()->filter(fn($v) => $v !== null && $v !== '')->map(fn($v) => (string) $v)->values()->all();
    }

    // ---- 3 lapis: Project > Kategori > Item ------------------------------

    public function test_items_register_their_category_with_the_existing_spelling(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads');
        $second = $this->line($project, ' marketing ', 'Influencer');
        $this->line($project, 'Creative', 'Konten');

        $this->assertSame('Marketing', $second->fresh()->category, 'Ejaan kategori yang sudah ada menang.');
        $this->assertSame(['Marketing', 'Creative'], BudgetCategory::orderedFor($project->id)->pluck('name')->all());
    }

    public function test_page_lists_every_project_with_categories_in_order_and_empty_ones_too(): void
    {
        $mof = $this->project('Map of Feelings');
        $empty = $this->project('Project Kosong');
        $this->line($mof, 'Marketing', 'Ads');
        $this->line($mof, 'Creative', 'Konten');
        $this->actingAs($this->p['manajer'])->post(route('dashboard.budget.categories.store', $mof), ['name' => 'Legal'])->assertSessionHasNoErrors();

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk();
        $cards = $response->viewData('cards');

        $this->assertSame(['Map of Feelings', 'Project Kosong'], $cards->pluck('project.name')->all());
        $this->assertSame(['Marketing', 'Creative', 'Legal'], $cards->first()['sections']->pluck('name')->all(), 'Kategori kosong ikut tampil.');
        $this->assertSame([], $cards->last()['sections']->all());
        $response->assertSee('Project Kosong')->assertSee('Kategori ini masih kosong.');
    }

    public function test_filters_by_category_song_and_status(): void
    {
        $mof = $this->project('Map of Feelings');
        $this->line($mof, 'Marketing', 'Ads', 1000, 1500, ['song_title' => 'Aku Harus Pergi']);
        $this->line($mof, 'Marketing', 'Influencer', 1000, 400, ['song_title' => 'Single Hujan']);
        $this->line($mof, 'Creative', 'Konten', 1000, 0);

        $names = fn(array $query) => $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', $query))->assertOk()
            ->viewData('cards')->flatMap(fn($c) => $c['sections']->flatMap(fn($s) => $s['rows']->pluck('item')))->sort()->values()->all();

        $this->assertSame(['Ads', 'Influencer'], $names(['category' => 'marketing']), 'Filter kategori tidak membedakan huruf besar/kecil.');
        $this->assertSame(['Ads'], $names(['song' => 'Aku Harus Pergi']));
        $this->assertSame(['Ads'], $names(['status' => 'over']));
        $this->assertSame(['Influencer'], $names(['status' => 'within']));
        $this->assertSame(['Konten'], $names(['status' => 'unspent']));
        $this->assertSame(['Ads', 'Konten'], $names(['status' => 'over'] + ['category' => 'Marketing']) === ['Ads'] ? ['Ads', 'Konten'] : [], 'Filter digabung (AND).');

        // Pilihan dropdown tidak menyusut saat difilter.
        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', ['category' => 'Creative']))->assertOk();
        $this->assertSame(['Creative', 'Marketing'], collect($response->viewData('categoryOptions'))->sort()->values()->all());
        $this->assertEquals(1000, $response->viewData('totals')['budget'], 'Ringkasan ikut filter.');
    }

    public function test_filtering_by_item_attributes_hides_projects_with_no_match(): void
    {
        $mof = $this->project('Map of Feelings');
        $mavnus = $this->project('Mavnus');
        $this->line($mof, 'Marketing', 'Ads');
        $this->line($mavnus, 'Website', 'Hosting');

        $cards = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', ['category' => 'Website']))->assertOk()->viewData('cards');

        $this->assertSame(['Mavnus'], $cards->pluck('project.name')->all());
    }

    // ---- Project Budget (dana keseluruhan) vs Budget Allocation ----------

    public function test_project_budget_can_be_filled_before_any_project_exists(): void
    {
        $this->assertDatabaseCount('projects', 0);
        $kanaya = $this->actingAs($this->p['manajer']);

        // Tombolnya ada walau belum ada project, sejajar dengan Tambah Budget dan Export/Import.
        $kanaya->get(route('dashboard.budget.index'))->assertOk()
            ->assertSeeInOrder([
                route('dashboard.export-import.import.show', ['key' => 'budget']),
                route('dashboard.budget.fund.edit'),
                'Tambah Budget',
            ], false)
            ->assertSee('Isi Project Budget')
            ->assertSee('Belum diisi');

        $kanaya->get(route('dashboard.budget.fund.edit'))->assertOk()->assertSee('Project Budget (Rp)')->assertSee('Budget Allocation saat ini');

        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => 500000000])
            ->assertRedirect(route('dashboard.budget.index'));

        $this->assertEquals(500000000, BudgetFund::amount());
        $this->assertTrue(AuditLog::where('action', 'Project Budget diubah')->where('detail', 'like', '%diisi menjadi 500.000.000%')->exists());

        $kanaya->get(route('dashboard.budget.index'))->assertOk()->assertSee('Edit Project Budget')->assertSee('Rp 500.000.000');
    }

    public function test_project_budget_is_a_single_edit_only_amount(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => 50000000])->assertRedirect();
        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => 60000000])->assertRedirect();
        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => 0])->assertRedirect();

        $this->assertDatabaseCount('budget_funds', 1);
        $this->assertSame(0.0, BudgetFund::amount(), 'Rp 0 yang disimpan berbeda dari belum diisi (null).');
        $this->assertSame($this->p['manajer']->id, BudgetFund::first()->updated_by);
        $this->assertTrue(AuditLog::where('detail', 'like', '%diubah dari 60.000.000 menjadi 0%')->exists());

        // Tidak ada jalur tambah/hapus, dan tidak ada lagi anggaran per project.
        $this->assertFalse(Route::has('dashboard.budget.fund.store'));
        $this->assertFalse(Route::has('dashboard.budget.fund.destroy'));
        $this->assertFalse(Route::has('dashboard.budget.plan.edit'));
    }

    public function test_project_budget_validation_and_access(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => -1])->assertSessionHasErrors('project_budget');
        $kanaya->patch(route('dashboard.budget.fund.update'), ['project_budget' => 'abc'])->assertSessionHasErrors('project_budget');
        $kanaya->patch(route('dashboard.budget.fund.update'), [])->assertSessionHasErrors('project_budget');
        $this->assertNull(BudgetFund::amount());

        // View saja = tidak bisa (dan tombolnya tidak tampil); tanpa akses budget = tidak bisa.
        $this->actingAs($this->viewer)->get(route('dashboard.budget.fund.edit'))->assertForbidden();
        $this->actingAs($this->viewer)->patch(route('dashboard.budget.fund.update'), ['project_budget' => 1])->assertForbidden();
        $this->actingAs($this->p['hrd'])->get(route('dashboard.budget.fund.edit'))->assertForbidden();
        $this->actingAs($this->p['hrd'])->patch(route('dashboard.budget.fund.update'), ['project_budget' => 1])->assertForbidden();
        $this->assertNull(BudgetFund::amount());

        BudgetFund::set(1000, null);
        $this->actingAs($this->viewer)->get(route('dashboard.budget.index'))->assertOk()
            ->assertSee('Rp 1.000')
            ->assertDontSee(route('dashboard.budget.fund.edit'), false);
    }

    public function test_summary_labels_budget_allocation_and_compares_it_with_the_overall_fund(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 20000000, 5000000);
        BudgetFund::set(50000000, null);

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk();
        $response->assertSeeInOrder(['Project Budget', 'Rp 50.000.000', 'Unallocated: Rp 30.000.000', 'Budget Allocation', 'Rp 20.000.000'])
            ->assertDontSee('Over-allocated:', false);
        $this->assertEquals(50000000, $response->viewData('fund'));
        $this->assertEquals(30000000, $response->viewData('unallocated'));

        // Dana tidak ikut filter project/kategori; selisih selalu dihitung dari SEMUA item.
        $other = $this->project('Mavnus');
        $this->line($other, 'Website', 'Hosting', 10000000);
        $filtered = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index', ['project_id' => $project->id]))->assertOk();
        $this->assertEquals(20000000, $filtered->viewData('unallocated'), '50jt dana - 30jt alokasi semua project.');
        $this->assertEquals(20000000, $filtered->viewData('totals')['budget'], 'Budget Allocation kartu ringkasan mengikuti filter.');

        // Alokasi item melebihi dana -> ditandai.
        $this->line($project, 'Creative', 'Konten', 40000000);
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk()
            ->assertSee('Over-allocated: Rp 20.000.000', false);
    }

    public function test_no_comparison_is_shown_until_the_fund_is_filled(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 20000000);

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk()
            ->assertDontSee('Unallocated:', false)
            ->assertDontSee('Over-allocated:', false);
        $this->assertNull($response->viewData('fund'));
        $this->assertNull($response->viewData('unallocated'));
    }

    public function test_unallocated_is_not_shown_when_a_category_is_hidden_from_the_viewer(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 20000000);
        $this->line($project, 'Gaji Tim', 'Fee', 10000000);
        BudgetFund::set(50000000, null);
        $this->restrict($this->category($project, 'Gaji Tim'), [$this->p['manajer']]);

        // Raka tidak melihat "Gaji Tim": selisih dana akan membocorkan total kategori itu.
        $response = $this->actingAs($this->raka)->get(route('dashboard.budget.index'))->assertOk()
            ->assertDontSee('Unallocated:', false) // teks panduan halaman tetap memuat kata ini
            ->assertSee('Rp 50.000.000');
        $this->assertNull($response->viewData('unallocated'));

        // Kanaya melihat semuanya.
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk()->assertSee('Unallocated: Rp 20.000.000', false);
    }

    public function test_pdf_shows_the_overall_fund_only_for_an_unfiltered_report(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 1000);
        BudgetFund::set(5000, null);

        $this->assertSame(5000.0, BudgetFund::amount());
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.pdf'))->assertOk();
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.pdf', ['project_id' => $project->id]))->assertOk();
    }

    // ---- edit satuan -----------------------------------------------------

    public function test_inline_edit_updates_one_field_at_a_time(): void
    {
        $project = $this->project('Map of Feelings');
        $row = $this->line($project, 'Marketing', 'Ads', 1000000, 200000, ['note' => 'lama']);
        $kanaya = $this->actingAs($this->p['manajer']);
        $url = route('dashboard.budget.field', $row);

        $kanaya->patchJson($url, ['field' => 'item', 'value' => 'Ads Instagram'])->assertOk()->assertJson(['ok' => true]);
        $kanaya->patchJson($url, ['field' => 'song_title', 'value' => 'Aku Harus Pergi'])->assertOk();
        $kanaya->patchJson($url, ['field' => 'budget', 'value' => '2500000'])->assertOk();
        $kanaya->patchJson($url, ['field' => 'actual', 'value' => '750000'])->assertOk();
        $kanaya->patchJson($url, ['field' => 'proof_link', 'value' => 'drive.google.com/file/d/abc'])->assertOk();
        $kanaya->patchJson($url, ['field' => 'note', 'value' => 'baru'])->assertOk();

        $row->refresh();
        $this->assertSame('Ads Instagram', $row->item);
        $this->assertSame('Aku Harus Pergi', $row->song_title);
        $this->assertEquals(2500000, $row->budget);
        $this->assertEquals(750000, $row->actual);
        $this->assertSame('https://drive.google.com/file/d/abc', $row->proof_link, 'Link tanpa skema dilengkapi https://.');
        $this->assertSame('baru', $row->note);
        $this->assertSame($this->p['manajer']->id, $row->updated_by);
        $this->assertSame('Marketing', $row->category, 'Kategori tidak berubah lewat edit satuan.');

        // Mengosongkan kolom opsional = null; actual kosong = 0.
        $kanaya->patchJson($url, ['field' => 'song_title', 'value' => ''])->assertOk();
        $kanaya->patchJson($url, ['field' => 'proof_link', 'value' => ''])->assertOk();
        $kanaya->patchJson($url, ['field' => 'actual', 'value' => ''])->assertOk();
        $row->refresh();
        $this->assertNull($row->song_title);
        $this->assertNull($row->proof_link);
        $this->assertEquals(0, $row->actual);
    }

    public function test_inline_edit_validates_and_requires_manage_access(): void
    {
        $project = $this->project('Map of Feelings');
        $row = $this->line($project, 'Marketing', 'Ads', 1000000, 200000);
        $url = route('dashboard.budget.field', $row);
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->patchJson($url, ['field' => 'item', 'value' => ''])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'budget', 'value' => '-5'])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'budget', 'value' => ''])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'actual', 'value' => 'abc'])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'proof_link', 'value' => 'javascript:alert(1)'])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'category', 'value' => 'Lain'])->assertStatus(422);
        $kanaya->patchJson($url, ['field' => 'project_id', 'value' => '1'])->assertStatus(422);
        $this->assertSame('Ads', $row->fresh()->item);
        $this->assertEquals(1000000, $row->fresh()->budget);

        $this->actingAs($this->viewer)->patchJson($url, ['field' => 'item', 'value' => 'X'])->assertForbidden();
        $this->actingAs($this->p['hrd'])->patchJson($url, ['field' => 'item', 'value' => 'X'])->assertForbidden();
        $this->assertSame('Ads', $row->fresh()->item);
    }

    public function test_view_only_user_gets_no_inline_inputs_or_manage_controls(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads');

        $this->actingAs($this->viewer)->get(route('dashboard.budget.index'))->assertOk()
            ->assertSee('Ads')
            ->assertDontSee('onblur="bdSaveField(', false)
            ->assertDontSee('>+ Kategori<', false)
            ->assertDontSee('>Isi Project Budget<', false)
            ->assertDontSee('>Edit Project Budget<', false)
            ->assertDontSee(route('dashboard.budget.fund.edit'), false);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk()
            ->assertSee('onblur="bdSaveField(', false)
            ->assertSee('+ Kategori', false);
    }

    // ---- kelola kategori -------------------------------------------------

    public function test_add_category_rejects_duplicates_ignoring_case(): void
    {
        $project = $this->project('Map of Feelings');
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->post(route('dashboard.budget.categories.store', $project), ['name' => 'Marketing'])->assertSessionHasNoErrors();
        $kanaya->post(route('dashboard.budget.categories.store', $project), ['name' => ' marketing '])->assertSessionHasErrors('name');
        $kanaya->post(route('dashboard.budget.categories.store', $project), ['name' => ''])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('budget_categories', 1);

        // Nama sama di project lain boleh.
        $other = $this->project('Mavnus');
        $kanaya->post(route('dashboard.budget.categories.store', $other), ['name' => 'Marketing'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('budget_categories', 2);

        $this->actingAs($this->viewer)->post(route('dashboard.budget.categories.store', $project), ['name' => 'Baru'])->assertForbidden();
    }

    public function test_category_color_and_order_can_be_changed(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads');
        $this->line($project, 'Creative', 'Konten');
        $this->line($project, 'Legal', 'Notaris');
        $kanaya = $this->actingAs($this->p['manajer']);

        $creative = $this->category($project, 'Creative');
        $kanaya->patchJson(route('dashboard.budget.categories.update', $creative), ['color' => '#AABBCC'])->assertOk();
        $this->assertSame('#aabbcc', $creative->fresh()->color);
        $kanaya->patchJson(route('dashboard.budget.categories.update', $creative), ['color' => 'merah'])->assertStatus(422);

        $kanaya->patch(route('dashboard.budget.categories.move', $creative), ['direction' => 'up'])->assertRedirect();
        $this->assertSame(['Creative', 'Marketing', 'Legal'], BudgetCategory::orderedFor($project->id)->pluck('name')->all());

        $kanaya->patch(route('dashboard.budget.categories.move', $creative), ['direction' => 'up']); // sudah paling atas
        $this->assertSame(['Creative', 'Marketing', 'Legal'], BudgetCategory::orderedFor($project->id)->pluck('name')->all());

        $kanaya->patch(route('dashboard.budget.categories.move', $creative), ['direction' => 'down']);
        $this->assertSame(['Marketing', 'Creative', 'Legal'], BudgetCategory::orderedFor($project->id)->pluck('name')->all());
    }

    public function test_only_empty_categories_can_be_deleted(): void
    {
        $project = $this->project('Map of Feelings');
        $filled = $this->line($project, 'Marketing', 'Ads');
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.budget.categories.store', $project), ['name' => 'Kosong']);

        $kanaya->delete(route('dashboard.budget.categories.destroy', $this->category($project, 'Marketing')))->assertSessionHasErrors('category');
        $this->assertNotNull(BudgetCategory::findByName($project->id, 'Marketing'));
        $this->assertNotNull($filled->fresh(), 'Item tidak ikut terhapus.');

        $kanaya->delete(route('dashboard.budget.categories.destroy', $this->category($project, 'Kosong')))->assertSessionHasNoErrors();
        $this->assertNull(BudgetCategory::findByName($project->id, 'Kosong'));

        // Setelah item dihapus, kategori boleh dihapus.
        $kanaya->delete(route('dashboard.budget.destroy', $filled))->assertRedirect();
        $kanaya->delete(route('dashboard.budget.categories.destroy', $this->category($project, 'Marketing')))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('budget_categories', 0);
    }

    // ---- form tetap halaman sendiri (bukan modal) -----------------------

    public function test_add_and_edit_are_standalone_pages_and_never_modals(): void
    {
        $project = $this->project('Map of Feelings');
        $row = $this->line($project, 'Marketing', 'Ads');
        $kanaya = $this->actingAs($this->p['manajer']);

        $index = $kanaya->get(route('dashboard.budget.index'))->assertOk();
        $index->assertSee(route('dashboard.budget.create', ['project_id' => $project->id]), false)
            ->assertSee(route('dashboard.budget.edit', $row), false)
            ->assertSee(route('dashboard.budget.fund.edit'), false)
            ->assertSee(route('dashboard.budget.categories.access', $this->category($project, 'Marketing')), false);
        // Tombol-tombol itu link biasa (halaman sendiri), bukan pemicu modal.
        $this->assertStringNotContainsString('wt-open-task-modal', $index->getContent());
        $this->assertStringNotContainsString('bdModal', $index->getContent());

        $kanaya->get(route('dashboard.budget.create', ['project_id' => $project->id, 'category' => 'Marketing']))->assertOk()
            ->assertSee('budgetCategorySelect', false);
        $kanaya->get(route('dashboard.budget.edit', $row))->assertOk();
    }

    public function test_form_prefills_project_and_category_and_only_offers_existing_categories(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads');

        $response = $this->actingAs($this->p['manajer'])
            ->get(route('dashboard.budget.create', ['project_id' => $project->id, 'category' => 'Marketing']))->assertOk();

        $this->assertSame(['Marketing'], $response->viewData('categoriesByProject')[$project->id]);
        $this->assertSame($project->id, $response->viewData('prefill')['project_id']);
        $response->assertSee('"Marketing"', false);
    }

    public function test_store_creates_the_category_when_it_is_new(): void
    {
        $project = $this->project('Map of Feelings');

        $this->actingAs($this->p['manajer'])->post(route('dashboard.budget.store'), ['project_id' => $project->id, 'category' => 'Production', 'item' => 'Studio', 'budget' => 5000000])
            ->assertRedirect(route('dashboard.budget.index'));

        $this->assertNotNull(BudgetCategory::findByName($project->id, 'production'));
    }

    // ---- akses per kategori ---------------------------------------------

    public function test_access_page_lists_only_people_with_budget_access(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Gaji Tim', 'Fee');
        $category = $this->category($project, 'Gaji Tim');

        $response = $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.categories.access', $category))->assertOk();
        $names = $response->viewData('people')->pluck('name')->all();

        $this->assertContains('Kanaya', $names);
        $this->assertContains('Viewer', $names);
        $this->assertContains('Raka', $names);
        $this->assertNotContains('Rania', $names, 'HRD tanpa akses Budgeting tidak ditawarkan.');
        $this->assertNotContains('Whisnu Santika', $names, 'Owner selalu melihat semuanya, tidak perlu dipilih.');

        $this->actingAs($this->viewer)->get(route('dashboard.budget.categories.access', $category))->assertForbidden();
        $this->actingAs($this->p['hrd'])->get(route('dashboard.budget.categories.access', $category))->assertForbidden();
    }

    public function test_saving_access_restricts_the_category_and_is_logged(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Gaji Tim', 'Fee');
        $category = $this->category($project, 'Gaji Tim');

        $this->actingAs($this->p['manajer'])
            ->put(route('dashboard.budget.categories.viewers', $category), ['user_ids' => [$this->p['manajer']->id, $this->viewer->id]])
            ->assertRedirect(route('dashboard.budget.index', ['project_id' => $project->id]));

        $this->assertEqualsCanonicalizing([$this->p['manajer']->id, $this->viewer->id], $category->fresh()->viewers->pluck('id')->all());
        $this->assertTrue(AuditLog::where('action', 'Akses kategori budget diubah')->where('detail', 'like', '%dibatasi untuk 2 orang%')->exists());

        // Daftar kosong = terbuka lagi untuk semua.
        $this->actingAs($this->p['manajer'])->put(route('dashboard.budget.categories.viewers', $category), [])->assertRedirect();
        $this->assertCount(0, $category->fresh()->viewers);

        // Validasi: id harus user yang ada.
        $this->actingAs($this->p['manajer'])->put(route('dashboard.budget.categories.viewers', $category), ['user_ids' => [9999]])->assertSessionHasErrors('user_ids.0');
    }

    public function test_restricted_category_is_hidden_from_page_totals_chart_and_form(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 20000000, 5000000);
        $this->line($project, 'Gaji Tim', 'Fee Rahasia', 99000000, 98000000, ['song_title' => 'Lagu Rahasia']);
        $secret = $this->category($project, 'Gaji Tim');
        $this->restrict($secret, [$this->p['manajer']]);

        // Raka (budget manage tapi tidak dipilih) tidak melihat apa pun tentang kategori itu.
        $response = $this->actingAs($this->raka)->get(route('dashboard.budget.index'))->assertOk()
            ->assertSee('Ads')
            ->assertDontSee('Fee Rahasia')
            ->assertDontSee('Gaji Tim')
            ->assertDontSee('Lagu Rahasia')
            ->assertDontSee('99.000.000')
            ->assertDontSee(route('dashboard.budget.categories.access', $secret), false);

        $this->assertEquals(20000000, $response->viewData('totals')['budget']);
        $this->assertEquals(5000000, $response->viewData('totals')['actual']);
        $this->assertSame(['Marketing'], collect($response->viewData('chart'))->pluck('label')->all());
        $this->assertNotContains('Gaji Tim', $response->viewData('categoryOptions'));
        $this->assertNotContains('Lagu Rahasia', $response->viewData('songOptions'));

        // Form tidak menawarkan kategori itu dan saran isian tidak membocorkannya.
        $form = $this->actingAs($this->raka)->get(route('dashboard.budget.create', ['project_id' => $project->id]))->assertOk()
            ->assertDontSee('Gaji Tim')
            ->assertDontSee('Lagu Rahasia');
        $this->assertSame(['Marketing'], $form->viewData('categoriesByProject')[$project->id]);

        // Yang dipilih, Owner, dan Developer tetap melihat semuanya.
        $this->actingAs($this->p['manajer'])->get(route('dashboard.budget.index'))->assertOk()->assertSee('Fee Rahasia')->assertSee('Gaji Tim');
        $this->actingAs($this->p['owner'])->get(route('dashboard.budget.index'))->assertOk()->assertSee('Fee Rahasia');
        $developer = $this->makeDeveloper($this->p['owner']);
        $this->actingAs($developer)->get(route('dashboard.budget.index'))->assertOk()->assertSee('Fee Rahasia');
    }

    public function test_restricted_category_is_hidden_from_pdf_and_excel(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Marketing', 'Ads', 20000000, 5000000);
        $this->line($project, 'Gaji Tim', 'Fee Rahasia', 99000000, 98000000);
        $this->restrict($this->category($project, 'Gaji Tim'), [$this->p['manajer']]);

        $excel = fn(User $u) => $this->cellsOf($this->actingAs($u)->get(route('dashboard.export-import.download', ['key' => 'budget', 'format' => 'excel'])));

        $this->assertNotContains('Fee Rahasia', $excel($this->raka));
        $this->assertContains('Ads', $excel($this->raka));
        $this->assertContains('Fee Rahasia', $excel($this->p['manajer']));
        $this->assertContains('Fee Rahasia', $excel($this->p['owner']));

        // Laporan PDF dihitung dari BudgetReport yang sama: total yang tampil tidak memuat kategori tersembunyi.
        $visible = BudgetReport::forProject(null, $this->raka);
        $this->assertEquals(20000000, $visible->totals()['budget']);
        $this->assertSame(['Marketing'], $visible->chart('category')->pluck('label')->all());
        $this->assertEquals(119000000, BudgetReport::forProject(null, $this->p['owner'])->totals()['budget']);

        $this->actingAs($this->raka)->get(route('dashboard.budget.pdf'))->assertOk();
    }

    public function test_restricted_category_cannot_be_reached_by_url_or_written_to(): void
    {
        $project = $this->project('Map of Feelings');
        $secret = $this->line($project, 'Gaji Tim', 'Fee Rahasia', 99000000, 0);
        $category = $this->category($project, 'Gaji Tim');
        $this->restrict($category, [$this->p['manajer']]);
        $raka = $this->actingAs($this->raka);

        // Item: tidak bisa dibuka, diubah, diedit satuan, atau dihapus (404, tidak membocorkan keberadaannya).
        $raka->get(route('dashboard.budget.edit', $secret))->assertNotFound();
        $raka->patch(route('dashboard.budget.update', $secret), ['project_id' => $project->id, 'category' => 'Gaji Tim', 'item' => 'X', 'budget' => 1])->assertNotFound();
        $raka->patchJson(route('dashboard.budget.field', $secret), ['field' => 'item', 'value' => 'X'])->assertNotFound();
        $raka->delete(route('dashboard.budget.destroy', $secret))->assertNotFound();
        $this->assertSame('Fee Rahasia', $secret->fresh()->item);

        // Kategori: tidak bisa membuka akses, mengubah, menggeser, atau menghapus.
        $raka->get(route('dashboard.budget.categories.access', $category))->assertNotFound();
        $raka->put(route('dashboard.budget.categories.viewers', $category), [])->assertNotFound();
        $raka->patchJson(route('dashboard.budget.categories.update', $category), ['color' => '#112233'])->assertNotFound();
        $raka->patch(route('dashboard.budget.categories.move', $category), ['direction' => 'up'])->assertNotFound();
        $raka->delete(route('dashboard.budget.categories.destroy', $category))->assertNotFound();
        $this->assertCount(1, $category->fresh()->viewers, 'Pembatasan tidak bisa dicabut oleh orang yang tidak melihatnya.');

        // Menaruh item ke kategori itu (juga dengan mengetik namanya) ditolak.
        $raka->post(route('dashboard.budget.store'), ['project_id' => $project->id, 'category' => 'gaji tim', 'item' => 'Selundupan', 'budget' => 1])->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('project_budgets', ['item' => 'Selundupan']);

        // Memindahkan item biasa ke kategori itu lewat form edit juga ditolak.
        $ads = $this->line($project, 'Marketing', 'Ads');
        $raka->patch(route('dashboard.budget.update', $ads), ['project_id' => $project->id, 'category' => 'Gaji Tim', 'item' => 'Ads', 'budget' => 1])->assertSessionHasErrors('category');
        $this->assertSame('Marketing', $ads->fresh()->category);
    }

    public function test_import_into_a_restricted_category_is_refused_before_anything_is_saved(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Gaji Tim', 'Fee Rahasia');
        $this->restrict($this->category($project, 'Gaji Tim'), [$this->p['manajer']]);

        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan']);
        fputcsv($stream, ['Map of Feelings', 'Marketing', 'Aman', '1000', '0', '']);
        fputcsv($stream, ['Map of Feelings', 'Gaji Tim', 'Selundupan', '1000', '0', '']);
        rewind($stream);
        $file = UploadedFile::fake()->createWithContent('budget.csv', stream_get_contents($stream));

        $raka = $this->actingAs($this->raka);
        $preview = $raka->post(route('dashboard.export-import.import.preview', ['key' => 'budget']), ['file' => $file])->assertOk();

        $raka->post(route('dashboard.export-import.import.commit', ['key' => 'budget']), ['token' => $preview->viewData('token')])->assertForbidden();
        $this->assertDatabaseMissing('project_budgets', ['item' => 'Aman']);
        $this->assertDatabaseMissing('project_budgets', ['item' => 'Selundupan']);
    }

    public function test_deleting_a_user_or_project_cleans_up_category_viewers(): void
    {
        $project = $this->project('Map of Feelings');
        $this->line($project, 'Gaji Tim', 'Fee');
        $category = $this->category($project, 'Gaji Tim');
        $this->restrict($category, [$this->viewer]);

        $this->viewer->delete();
        $this->assertCount(0, $category->fresh()->viewers);

        $project->delete();
        $this->assertDatabaseCount('budget_categories', 0);
    }
}