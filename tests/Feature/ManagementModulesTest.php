<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\EmployeeContract;
use App\Models\Kpi;
use App\Models\LegalDocument;
use App\Models\OvertimeRequest;
use App\Models\PayrollRecord;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\RoyaltyEntry;
use App\Models\SystemChangelog;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian H — modul manajerial: KPI (H1), Kontrak (H2),
 * Payroll (H3–H8), Budgeting (H9), Royalty (H10), Legal (H11), IT (H12–H13),
 * dan pembatasan view vs manage (H14).
 * Kanaya = budget/royalty/kpi/contracts/payroll manage + legal/it view;
 * Rania = kpi view (+ people, recruitment).
 */
class ManagementModulesTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting(['shortage_deduction_rate' => 25000]);
        $this->disk = Storage::fake('local');
        Storage::fake('public');
        $this->p = $this->company();
    }

    private function pdf(string $name = 'dokumen.pdf', int $kb = 20): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    // =====================================================================
    // KPI (H1)
    // =====================================================================

    private function kpiPayload(array $o = []): array
    {
        return array_merge([
            'employee_id' => $this->p['gepeng']->id,
            'title' => 'Posting konten',
            'period' => '2026-Q3',
            'target' => 30,
            'current' => 12,
            'unit' => 'post',
            'weight' => 40,
            'due_date' => '2026-09-30',
            'status' => 'Active',
            'owner_note' => 'Fokus video pendek',
        ], $o);
    }

    public function test_kpi_crud_by_a_manager_of_the_module(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->get(route('dashboard.kpi.index'))->assertOk();
        $kanaya->get(route('dashboard.kpi.create'))->assertOk();
        $kanaya->post(route('dashboard.kpi.store'), $this->kpiPayload())->assertRedirect(route('dashboard.kpi.index'));

        $kpi = Kpi::sole();
        $this->assertSame('Posting konten', $kpi->title);
        $this->assertSame($this->p['manajer']->id, $kpi->created_by);
        $this->assertEquals(30, $kpi->target);

        $kanaya->get(route('dashboard.kpi.edit', $kpi))->assertOk();
        $kanaya->patch(route('dashboard.kpi.update', $kpi), $this->kpiPayload(['current' => 30, 'status' => 'Completed']))
            ->assertRedirect(route('dashboard.kpi.index'));
        $this->assertEquals(30, $kpi->fresh()->current);
        $this->assertSame('Completed', $kpi->fresh()->status);

        $kanaya->delete(route('dashboard.kpi.destroy', $kpi))->assertRedirect();
        $this->assertDatabaseCount('kpis', 0);
    }

    public function test_kpi_validation(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $post = fn(array $o) => $kanaya->post(route('dashboard.kpi.store'), $this->kpiPayload($o));

        $post(['employee_id' => 9999])->assertSessionHasErrors('employee_id');
        $post(['title' => ''])->assertSessionHasErrors('title');
        $post(['target' => -1])->assertSessionHasErrors('target');
        $post(['current' => 'banyak'])->assertSessionHasErrors('current');
        $post(['weight' => 101])->assertSessionHasErrors('weight');
        $post(['status' => 'Batal'])->assertSessionHasErrors('status');
        $post(['period' => ''])->assertSessionHasErrors('period');

        $this->assertDatabaseCount('kpis', 0);
    }

    public function test_kpi_view_only_user_can_read_but_not_change(): void
    {
        $kpi = Kpi::create($this->kpiPayload() + ['created_by' => $this->p['manajer']->id]);
        $rania = $this->actingAs($this->p['hrd']);

        $rania->get(route('dashboard.kpi.index'))->assertOk()->assertSee('Posting konten');
        $rania->get(route('dashboard.kpi.create'))->assertForbidden();
        $rania->post(route('dashboard.kpi.store'), $this->kpiPayload())->assertForbidden();
        $rania->get(route('dashboard.kpi.edit', $kpi))->assertForbidden();
        $rania->patch(route('dashboard.kpi.update', $kpi), $this->kpiPayload(['current' => 1]))->assertForbidden();
        $rania->delete(route('dashboard.kpi.destroy', $kpi))->assertForbidden();

        $this->assertEquals(12, $kpi->fresh()->current);
    }

    // =====================================================================
    // Kontrak karyawan (H2) — akses file sudah dicakup PrivateFileAccessTest
    // =====================================================================

    public function test_contract_upload_validation_rejects_wrong_types_and_oversize(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $post = fn(UploadedFile $f) => $kanaya->post(route('dashboard.contracts.store'), ['employee_id' => $this->p['aldora']->id, 'file' => $f]);

        $post(UploadedFile::fake()->create('virus.exe', 20, 'application/x-msdownload'))->assertSessionHasErrors('file');
        $post(UploadedFile::fake()->create('skrip.php', 5, 'text/x-php'))->assertSessionHasErrors('file');
        $post($this->pdf('besar.pdf', 10241))->assertSessionHasErrors('file'); // > 10 MB
        $kanaya->post(route('dashboard.contracts.store'), ['employee_id' => $this->p['aldora']->id])->assertSessionHasErrors('file');
        $kanaya->post(route('dashboard.contracts.store'), ['employee_id' => 9999, 'file' => $this->pdf()])->assertSessionHasErrors('employee_id');
        $kanaya->post(route('dashboard.contracts.store'), [
            'employee_id' => $this->p['aldora']->id,
            'file' => $this->pdf(),
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-01',
        ])->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('employee_contracts', 0);
        $this->assertSame([], $this->disk->allFiles());
    }

    public function test_contract_accepts_pdf_doc_and_images_up_to_10mb(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);

        foreach ([$this->pdf('a.pdf'), UploadedFile::fake()->create('b.docx', 50), UploadedFile::fake()->image('c.jpg'), $this->pdf('max.pdf', 10240)] as $file) {
            $kanaya->post(route('dashboard.contracts.store'), [
                'employee_id' => $this->p['aldora']->id,
                'file' => $file,
                'start_date' => '2026-01-01',
                'end_date' => '2027-01-01',
                'notes' => 'Kontrak tahunan',
            ])->assertRedirect(route('dashboard.contracts.index'))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('employee_contracts', 4);
    }

    public function test_contract_edit_replaces_the_file_and_delete_removes_it(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.contracts.store'), ['employee_id' => $this->p['aldora']->id, 'file' => $this->pdf('lama.pdf')]);
        $contract = EmployeeContract::sole();
        $oldPath = $contract->file_path;
        $this->disk->assertExists($oldPath);

        $kanaya->get(route('dashboard.contracts.edit', $contract))->assertOk();

        // Edit tanpa file → file lama tetap.
        $kanaya->patch(route('dashboard.contracts.update', $contract), ['employee_id' => $this->p['aldora']->id, 'notes' => 'Catatan baru'])
            ->assertRedirect(route('dashboard.contracts.index'));
        $this->assertSame($oldPath, $contract->fresh()->file_path);
        $this->assertSame('Catatan baru', $contract->fresh()->notes);

        // Edit dengan file baru → file lama terhapus.
        $kanaya->patch(route('dashboard.contracts.update', $contract), ['employee_id' => $this->p['aldora']->id, 'file' => $this->pdf('baru.pdf')]);
        $contract->refresh();
        $this->assertNotSame($oldPath, $contract->file_path);
        $this->disk->assertMissing($oldPath);
        $this->disk->assertExists($contract->file_path);
        $this->assertSame('baru.pdf', $contract->original_filename);

        $kanaya->delete(route('dashboard.contracts.destroy', $contract))->assertRedirect();
        $this->disk->assertMissing($contract->file_path);
        $this->assertDatabaseCount('employee_contracts', 0);
    }

    public function test_contract_management_requires_manage_access(): void
    {
        $this->grant($this->p['gepeng'], 'contracts', 'view');
        $contract = EmployeeContract::create([
            'employee_id' => $this->p['aldora']->id,
            'file_path' => 'contracts/1/x.pdf',
            'original_filename' => 'x.pdf',
            'uploaded_by' => $this->p['owner']->id,
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $g = $this->actingAs($this->p['gepeng']);
        $g->get(route('dashboard.contracts.index'))->assertOk();
        $g->get(route('dashboard.contracts.create'))->assertForbidden();
        $g->post(route('dashboard.contracts.store'), ['employee_id' => 1, 'file' => $this->pdf()])->assertForbidden();
        $g->get(route('dashboard.contracts.edit', $contract))->assertForbidden();
        $g->delete(route('dashboard.contracts.destroy', $contract))->assertForbidden();
    }

    // =====================================================================
    // Payroll (H3–H8)
    // =====================================================================

    private function shortDay(User $u, string $date = '2026-09-15'): void
    {
        // 09:30–13:30 = 240 menit dari wajib 480 → kurang 240 menit = 4 blok jam.
        Attendance::create([
            'user_id' => $u->id,
            'date' => $date,
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => "{$date} 09:30:00",
            'clock_out_at' => "{$date} 13:30:00"
        ]);
    }

    public function test_generate_computes_base_plus_overtime_minus_shortage_for_everyone_with_a_salary(): void
    {
        $this->shortDay($this->p['aldora']);
        OvertimeRequest::create(['user_id' => $this->p['aldora']->id, 'date' => '2026-09-16', 'reason' => 'Rilis', 'status' => 'disetujui']);
        OvertimeRequest::create(['user_id' => $this->p['aldora']->id, 'date' => '2026-09-17', 'reason' => 'Pending', 'status' => 'pending']);
        $this->makeUser('karyawan', [], null, ['salary_base' => null]); // tanpa gaji → tidak di-generate

        $this->actingAs($this->p['manajer'])->get(route('dashboard.payroll.index', ['period' => '2026-09']))->assertOk();

        $this->post(route('dashboard.payroll.generate'), ['period' => '2026-09'])
            ->assertRedirect(route('dashboard.payroll.index', ['period' => '2026-09']))
            ->assertSessionHas('status', fn($m) => str_contains($m, '5 payroll berhasil digenerate untuk periode September 2026'));

        $this->assertSame(5, PayrollRecord::count());

        $aldora = PayrollRecord::where('user_id', $this->p['aldora']->id)->firstOrFail();
        $this->assertSame('draft', $aldora->status);
        $this->assertEquals(6000000, $aldora->base_salary);
        $this->assertEquals(40000, $aldora->overtime_amount);      // 1 lembur disetujui × 40.000
        $this->assertEquals(100000, $aldora->shortage_deduction);  // 4 blok × 25.000
        $this->assertEquals(5940000, $aldora->total);
        $this->assertSame($this->p['manajer']->id, $aldora->generated_by);

        $gepeng = PayrollRecord::where('user_id', $this->p['gepeng']->id)->firstOrFail();
        $this->assertEquals(6500000, $gepeng->total, 'Tanpa lembur & kekurangan jam, total = gaji pokok.');

        $this->assertDatabaseHas('audit_logs', ['action' => 'Payroll digenerate']);
    }

    public function test_generate_can_be_limited_to_selected_employees(): void
    {
        $this->actingAs($this->p['manajer'])->post(route('dashboard.payroll.generate'), [
            'period' => '2026-09',
            'employee_ids' => [$this->p['aldora']->id],
        ])->assertRedirect();

        $this->assertSame([$this->p['aldora']->id], PayrollRecord::pluck('user_id')->all());
    }

    public function test_generate_validation(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->post(route('dashboard.payroll.generate'), [])->assertSessionHasErrors('period');
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '09-2026'])->assertSessionHasErrors('period');
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09', 'employee_ids' => [9999]])->assertSessionHasErrors('employee_ids.0');

        $this->assertDatabaseCount('payroll_records', 0);
    }

    public function test_regenerate_overwrites_drafts_but_skips_finalised_records(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09']);

        $locked = PayrollRecord::where('user_id', $this->p['aldora']->id)->first();
        $locked->update(['status' => 'finalized']);

        // Gaji Gepeng naik → regenerate menimpa draft-nya, tapi tidak menyentuh yang final.
        $this->p['gepeng']->update(['salary_base' => 8000000]);
        $this->p['aldora']->update(['salary_base' => 9999999]);

        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09'])
            ->assertSessionHas('status', fn($m) => str_contains($m, '4 payroll berhasil digenerate') && str_contains($m, '1 dilewati'));

        $this->assertEquals(8000000, PayrollRecord::where('user_id', $this->p['gepeng']->id)->value('total'));
        $this->assertEquals(6000000, $locked->fresh()->base_salary, 'Payroll final tidak boleh ditimpa.');
        $this->assertSame(5, PayrollRecord::count(), 'Tidak boleh dobel.');
    }

    public function test_adjustment_changes_the_total_and_only_drafts_can_be_edited(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09', 'employee_ids' => [$this->p['gepeng']->id]]);
        $record = PayrollRecord::sole();

        $kanaya->get(route('dashboard.payroll.show', $record))->assertOk();

        $kanaya->patch(route('dashboard.payroll.update', $record), ['other_adjustment' => -250000, 'notes' => 'Potongan kasbon'])
            ->assertRedirect(route('dashboard.payroll.show', $record))
            ->assertSessionHas('status', 'Penyesuaian payroll disimpan.');

        $record->refresh();
        $this->assertEquals(-250000, $record->other_adjustment);
        $this->assertEquals(6250000, $record->total);
        $this->assertSame('Potongan kasbon', $record->notes);

        $kanaya->patch(route('dashboard.payroll.update', $record), ['other_adjustment' => 'banyak'])->assertSessionHasErrors('other_adjustment');

        $record->update(['status' => 'finalized']);
        $kanaya->patch(route('dashboard.payroll.update', $record), ['other_adjustment' => 999999])
            ->assertSessionHas('error', 'Payroll yang sudah difinalisasi/dibayar tidak bisa diubah lagi.');
        $this->assertEquals(-250000, $record->fresh()->other_adjustment);
    }

    public function test_status_flow_is_one_way_draft_final_paid(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09', 'employee_ids' => [$this->p['gepeng']->id]]);
        $record = PayrollRecord::sole();

        // Draft belum boleh ditandai dibayar.
        $kanaya->post(route('dashboard.payroll.mark-paid', $record))->assertSessionHas('error', 'Cuma payroll berstatus final yang bisa ditandai dibayar.');
        $this->assertSame('draft', $record->fresh()->status);

        $kanaya->post(route('dashboard.payroll.finalize', $record))->assertSessionHas('status');
        $this->assertSame('finalized', $record->fresh()->status);
        $kanaya->post(route('dashboard.payroll.finalize', $record))->assertSessionHas('error', 'Cuma payroll berstatus draft yang bisa difinalisasi.');

        // Final tidak boleh dihapus.
        $kanaya->delete(route('dashboard.payroll.destroy', $record))->assertSessionHas('error');
        $this->assertDatabaseCount('payroll_records', 1);

        $kanaya->post(route('dashboard.payroll.mark-paid', $record))->assertSessionHas('status');
        $this->assertSame('paid', $record->fresh()->status);
        $kanaya->post(route('dashboard.payroll.mark-paid', $record))->assertSessionHas('error');
        $kanaya->delete(route('dashboard.payroll.destroy', $record))->assertSessionHas('error');

        foreach (['Payroll difinalisasi', 'Payroll ditandai dibayar'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action]);
        }
    }

    public function test_draft_payroll_can_be_deleted(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $kanaya->post(route('dashboard.payroll.generate'), ['period' => '2026-09', 'employee_ids' => [$this->p['gepeng']->id]]);

        $kanaya->delete(route('dashboard.payroll.destroy', PayrollRecord::sole()))
            ->assertRedirect(route('dashboard.payroll.index', ['period' => '2026-09']))
            ->assertSessionHas('status', 'Payroll draft dihapus.');

        $this->assertDatabaseCount('payroll_records', 0);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Payroll draft dihapus']);
    }

    public function test_payroll_is_only_for_users_with_the_payroll_module(): void
    {
        $record = PayrollRecord::create([
            'user_id' => $this->p['gepeng']->id,
            'period' => '2026-09',
            'base_salary' => 1,
            'overtime_amount' => 0,
            'shortage_deduction' => 0,
            'total' => 1,
            'status' => 'draft',
            'generated_by' => $this->p['owner']->id,
        ]);

        foreach (['hrd', 'aldora', 'gepeng'] as $who) {
            $u = $this->actingAs($this->p[$who]);
            $u->get(route('dashboard.payroll.index'))->assertForbidden();
            $u->get(route('dashboard.payroll.show', $record))->assertForbidden();
            $u->post(route('dashboard.payroll.generate'), ['period' => '2026-09'])->assertForbidden();
            $u->post(route('dashboard.payroll.finalize', $record))->assertForbidden();
            $u->delete(route('dashboard.payroll.destroy', $record))->assertForbidden();
        }

        $this->assertSame('draft', $record->fresh()->status);
    }

    public function test_payroll_view_only_access_cannot_generate_or_change_status(): void
    {
        $this->grant($this->p['hrd'], 'payroll', 'view');
        $record = PayrollRecord::create([
            'user_id' => $this->p['gepeng']->id,
            'period' => '2026-09',
            'base_salary' => 1,
            'overtime_amount' => 0,
            'shortage_deduction' => 0,
            'total' => 1,
            'status' => 'draft',
            'generated_by' => $this->p['owner']->id,
        ]);

        $rania = $this->actingAs($this->p['hrd']);
        $rania->get(route('dashboard.payroll.index'))->assertOk();
        $rania->get(route('dashboard.payroll.show', $record))->assertOk();
        $rania->post(route('dashboard.payroll.generate'), ['period' => '2026-09'])->assertForbidden();
        $rania->post(route('dashboard.payroll.finalize', $record))->assertForbidden();
        $rania->patch(route('dashboard.payroll.update', $record), ['other_adjustment' => 5])->assertForbidden();
    }

    /**
     * Regresi: `Carbon::createFromFormat('Y-m', '2026-11')` memakai HARI ini,
     * sehingga di tanggal 31 bulan November "meluap" ke Desember dan payroll /
     * riwayat salah bulan. Sekarang dipakai format '!Y-m' (mulai tanggal 1).
     */
    public function test_month_parameters_do_not_overflow_on_the_31st(): void
    {
        $this->freezeWorkday('2026-10-31 10:00:00');

        $this->actingAs($this->p['manajer'])->post(route('dashboard.payroll.generate'), ['period' => '2026-11', 'employee_ids' => [$this->p['gepeng']->id]])
            ->assertSessionHas('status', fn($m) => str_contains($m, 'November 2026'));

        $record = PayrollRecord::sole();
        $this->assertSame('2026-11', $record->period);
        $this->assertSame('November 2026', $record->periodLabel());

        $this->actingAs($this->p['gepeng'])->get(route('employee.attendance.history', ['bulan' => '2026-11']))
            ->assertViewHas('currentMonth', '2026-11')
            ->assertViewHas('prevMonth', '2026-10')
            ->assertViewHas('nextMonth', '2026-12');

        $this->actingAs($this->p['owner'])->get(route('attendance.recap.show', [$this->p['gepeng'], 'bulan' => '2026-11']))
            ->assertViewHas('currentMonth', '2026-11')
            ->assertViewHas('nextMonth', '2026-12');

        $this->actingAs($this->p['gepeng'])->get(route('employee.workTracker.calendar', ['month' => '2026-11']))->assertOk();
    }

    // =====================================================================
    // Project Budgeting (H9)
    // =====================================================================

    public function test_budget_crud_and_overspend_shows_a_negative_remaining(): void
    {
        $project = Project::create(['name' => 'Album Q3', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
        $kanaya = $this->actingAs($this->p['manajer']);

        $kanaya->get(route('dashboard.budget.index'))->assertOk();
        $kanaya->get(route('dashboard.budget.create'))->assertOk();
        $kanaya->post(route('dashboard.budget.store'), ['project_id' => $project->id, 'category' => 'Produksi', 'item' => 'Studio', 'budget' => 5000000, 'actual' => 6500000, 'note' => 'Lembur studio'])
            ->assertRedirect(route('dashboard.budget.index'));
        $kanaya->post(route('dashboard.budget.store'), ['project_id' => $project->id, 'category' => 'Promosi', 'item' => 'Iklan', 'budget' => 2000000])->assertRedirect();

        $studio = ProjectBudget::where('item', 'Studio')->firstOrFail();
        $this->assertEquals(0, ProjectBudget::where('item', 'Iklan')->value('actual'), 'Realisasi kosong dianggap 0.');
        $this->assertSame($this->p['manajer']->id, $studio->updated_by);

        $totals = $project->fresh()->budgetTotals();
        $this->assertEquals(7000000, $totals['budget']);
        $this->assertEquals(6500000, $totals['actual']);
        $this->assertEquals(500000, $totals['remaining']);

        $kanaya->patch(route('dashboard.budget.update', $studio), ['project_id' => $project->id, 'category' => 'Produksi', 'item' => 'Studio', 'budget' => 5000000, 'actual' => 6500000])->assertRedirect();
        $this->assertEquals(-1500000, $studio->fresh()->budget - $studio->fresh()->actual, 'Realisasi melebihi budget → selisih minus.');

        $kanaya->get(route('dashboard.budget.edit', $studio))->assertOk();
        $kanaya->delete(route('dashboard.budget.destroy', $studio))->assertRedirect();
        $this->assertDatabaseCount('project_budgets', 1);
    }

    public function test_budget_validation_and_access(): void
    {
        $project = Project::create(['name' => 'Album Q3', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
        $kanaya = $this->actingAs($this->p['manajer']);
        $ok = ['project_id' => $project->id, 'category' => 'Produksi', 'item' => 'Studio', 'budget' => 1];

        $kanaya->post(route('dashboard.budget.store'), array_merge($ok, ['project_id' => 9999]))->assertSessionHasErrors('project_id');
        $kanaya->post(route('dashboard.budget.store'), array_merge($ok, ['budget' => -5]))->assertSessionHasErrors('budget');
        $kanaya->post(route('dashboard.budget.store'), array_merge($ok, ['item' => '']))->assertSessionHasErrors('item');
        $kanaya->post(route('dashboard.budget.store'), array_merge($ok, ['actual' => -1]))->assertSessionHasErrors('actual');
        $this->assertDatabaseCount('project_budgets', 0);

        $this->actingAs($this->p['hrd'])->get(route('dashboard.budget.index'))->assertForbidden();
        $this->actingAs($this->p['gepeng'])->post(route('dashboard.budget.store'), $ok)->assertForbidden();
    }

    // =====================================================================
    // Royalty (H10)
    // =====================================================================

    public function test_royalty_crud_status_filter_and_validation(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $payload = fn(array $o = []) => array_merge([
            'title' => 'Single Hujan',
            'period' => '2026-08',
            'source' => 'Spotify',
            'status' => 'Estimated',
            'gross' => 10000000,
            'share_pct' => 40,
            'recoup' => 1000000,
            'note' => 'Q3',
        ], $o);

        $kanaya->get(route('dashboard.royalty.create'))->assertOk();
        foreach (RoyaltyEntry::STATUSES as $i => $status) {
            $kanaya->post(route('dashboard.royalty.store'), $payload(['title' => "Lagu {$i}", 'status' => $status]))->assertRedirect(route('dashboard.royalty.index'));
        }
        $this->assertDatabaseCount('royalty_entries', 4);

        $kanaya->get(route('dashboard.royalty.index'))->assertOk();
        foreach (RoyaltyEntry::STATUSES as $i => $status) {
            $kanaya->get(route('dashboard.royalty.index', ['status' => $status]))
                ->assertOk()->assertSee("Lagu {$i}");
        }
        $kanaya->get(route('dashboard.royalty.index', ['status' => 'Paid']))->assertDontSee('Lagu 0');

        $entry = RoyaltyEntry::where('title', 'Lagu 0')->firstOrFail();
        $kanaya->get(route('dashboard.royalty.edit', $entry))->assertOk();
        $kanaya->patch(route('dashboard.royalty.update', $entry), $payload(['title' => 'Lagu Revisi', 'status' => 'Paid']))->assertRedirect();
        $this->assertSame('Paid', $entry->fresh()->status);
        $kanaya->delete(route('dashboard.royalty.destroy', $entry))->assertRedirect();
        $this->assertDatabaseCount('royalty_entries', 3);

        $kanaya->post(route('dashboard.royalty.store'), $payload(['share_pct' => 101]))->assertSessionHasErrors('share_pct');
        $kanaya->post(route('dashboard.royalty.store'), $payload(['status' => 'Lunas']))->assertSessionHasErrors('status');
        $kanaya->post(route('dashboard.royalty.store'), $payload(['period' => 'Agustus']))->assertSessionHasErrors('period');
        $kanaya->post(route('dashboard.royalty.store'), $payload(['gross' => -1]))->assertSessionHasErrors('gross');
        $kanaya->post(route('dashboard.royalty.store'), $payload(['title' => '']))->assertSessionHasErrors('title');
    }

    // =====================================================================
    // Legal (H11)
    // =====================================================================

    public function test_legal_documents_crud_category_filter_and_expiry_badge(): void
    {
        $kanaya = $this->grantLegalManage();

        $kanaya->get(route('dashboard.legal.create'))->assertOk();
        $kanaya->post(route('dashboard.legal.store'), ['category' => 'album', 'title' => 'Kontrak Album A', 'party' => 'Label X', 'file' => $this->pdf(), 'start_date' => '2026-01-01', 'end_date' => now()->addDays(10)->toDateString()])
            ->assertRedirect(route('dashboard.legal.index'));
        $kanaya->post(route('dashboard.legal.store'), ['category' => 'royalty', 'title' => 'Perjanjian Royalti B', 'file' => $this->pdf('r.pdf'), 'end_date' => now()->addYears(3)->toDateString()])
            ->assertRedirect();

        $album = LegalDocument::where('title', 'Kontrak Album A')->firstOrFail();
        $royalty = LegalDocument::where('title', 'Perjanjian Royalti B')->firstOrFail();

        $this->assertTrue($album->isExpiringSoon());
        $this->assertFalse($royalty->isExpiringSoon());

        $kanaya->get(route('dashboard.legal.index'))->assertOk()->assertSee('Kontrak Album A')->assertSee('Perjanjian Royalti B');
        $kanaya->get(route('dashboard.legal.index', ['category' => 'album']))->assertSee('Kontrak Album A')->assertDontSee('Perjanjian Royalti B');
        $kanaya->get(route('dashboard.legal.index', ['category' => 'royalty']))->assertSee('Perjanjian Royalti B')->assertDontSee('Kontrak Album A');

        $kanaya->get(route('dashboard.legal.edit', $album))->assertOk();
        $kanaya->patch(route('dashboard.legal.update', $album), ['category' => 'album', 'title' => 'Kontrak Album A (rev)'])->assertRedirect();
        $this->assertSame('Kontrak Album A (rev)', $album->fresh()->title);

        $oldPath = $album->file_path;
        $kanaya->patch(route('dashboard.legal.update', $album), ['category' => 'album', 'title' => 'Kontrak Album A (rev)', 'file' => $this->pdf('baru.pdf')]);
        $this->disk->assertMissing($oldPath);
        $this->disk->assertExists($album->fresh()->file_path);

        $kanaya->delete(route('dashboard.legal.destroy', $album))->assertRedirect();
        $this->disk->assertMissing($album->fresh()?->file_path ?? 'tidak-ada');
        $this->assertDatabaseCount('legal_documents', 1);
    }

    private function grantLegalManage()
    {
        $this->grant($this->p['manajer'], 'legal', 'manage');

        return $this->actingAs($this->p['manajer']->fresh());
    }

    public function test_legal_validation(): void
    {
        $kanaya = $this->grantLegalManage();
        $ok = ['category' => 'album', 'title' => 'X', 'file' => $this->pdf()];

        $kanaya->post(route('dashboard.legal.store'), array_merge($ok, ['category' => 'lainnya']))->assertSessionHasErrors('category');
        $kanaya->post(route('dashboard.legal.store'), array_merge($ok, ['title' => '']))->assertSessionHasErrors('title');
        $kanaya->post(route('dashboard.legal.store'), ['category' => 'album', 'title' => 'X'])->assertSessionHasErrors('file');
        $kanaya->post(route('dashboard.legal.store'), array_merge($ok, ['file' => UploadedFile::fake()->create('x.exe', 5)]))->assertSessionHasErrors('file');
        $kanaya->post(route('dashboard.legal.store'), array_merge($ok, ['start_date' => '2026-05-02', 'end_date' => '2026-05-01']))->assertSessionHasErrors('end_date');

        $this->assertDatabaseCount('legal_documents', 0);
    }

    // =====================================================================
    // IT: Audit Log & System Changelog (H12–H13)
    // =====================================================================

    public function test_audit_log_lists_entries_and_is_read_only(): void
    {
        AuditLog::record('Login sukses', 'Whisnu login.', $this->p['owner']);
        $entry = AuditLog::record('Backup sistem', 'Backup harian.', 'Sistem');

        $kanaya = $this->actingAs($this->p['manajer']); // it = view
        $kanaya->get(route('dashboard.it.index'))->assertOk()->assertSee('Login sukses')->assertSee('Backup sistem');

        $kanaya->post(route('dashboard.it.index'), ['action' => 'x'])->assertStatus(405);
        $kanaya->delete(route('dashboard.it.index'))->assertStatus(405);
        $this->assertDatabaseCount('audit_logs', 2);
        $this->assertNotNull($entry->fresh());
    }

    public function test_audit_log_captures_actions_made_through_the_app(): void
    {
        $this->actingAs($this->p['owner'])->post(route('owner.employees.store'), [
            'name' => 'Baru',
            'email' => 'baru@wsm.local',
            'password' => 'rahasia123',
            'role' => 'karyawan',
        ]);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.it.index'))->assertOk()->assertSee('Karyawan ditambahkan');
    }

    public function test_system_changelog_crud_and_module_list_is_split_into_an_array(): void
    {
        $this->grant($this->p['manajer'], 'it', 'manage');
        $kanaya = $this->actingAs($this->p['manajer']->fresh());
        $payload = fn(array $o = []) => array_merge([
            'version' => 'v2.1.0',
            'release_date' => '2026-09-30',
            'status' => 'Planned',
            'modules' => 'Payroll, KPI',
            'title' => 'Rilis Q3',
            'changes' => "Perbaikan payroll\nTambah export",
        ], $o);

        $kanaya->get(route('dashboard.it.changelog.index'))->assertOk();
        $kanaya->get(route('dashboard.it.changelog.create'))->assertOk();
        $kanaya->post(route('dashboard.it.changelog.store'), $payload())->assertRedirect(route('dashboard.it.changelog.index'));

        $log = SystemChangelog::sole();
        $this->assertSame('v2.1.0', $log->version);
        $this->assertIsArray($log->modules);
        $this->assertContains('Payroll', $log->modules);
        $this->assertIsArray($log->changes);
        $this->assertCount(2, $log->changes);

        $kanaya->get(route('dashboard.it.changelog.edit', $log))->assertOk();
        $kanaya->patch(route('dashboard.it.changelog.update', $log), $payload(['status' => 'Released', 'title' => 'Rilis Q3 final']))->assertRedirect();
        $this->assertSame('Released', $log->fresh()->status);

        $kanaya->delete(route('dashboard.it.changelog.destroy', $log))->assertRedirect();
        $this->assertDatabaseCount('system_changelogs', 0);

        $kanaya->post(route('dashboard.it.changelog.store'), $payload(['status' => 'Draft']))->assertSessionHasErrors('status');
        $kanaya->post(route('dashboard.it.changelog.store'), $payload(['version' => '']))->assertSessionHasErrors('version');
        $kanaya->post(route('dashboard.it.changelog.store'), $payload(['release_date' => 'kemarin']))->assertSessionHasErrors('release_date');
        $kanaya->post(route('dashboard.it.changelog.store'), $payload(['changes' => '']))->assertSessionHasErrors('changes');
    }

    // =====================================================================
    // H14: view vs manage
    // =====================================================================

    public function test_view_only_modules_hide_management_from_kanaya_and_payroll_is_closed_to_rania(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']); // legal & it = view

        $kanaya->get(route('dashboard.legal.index'))->assertOk();
        $kanaya->get(route('dashboard.legal.create'))->assertForbidden();
        $kanaya->post(route('dashboard.legal.store'), ['category' => 'album', 'title' => 'X', 'file' => $this->pdf()])->assertForbidden();
        $kanaya->get(route('dashboard.it.changelog.index'))->assertOk();
        $kanaya->get(route('dashboard.it.changelog.create'))->assertForbidden();
        $kanaya->post(route('dashboard.it.changelog.store'), ['version' => 'v1'])->assertForbidden();

        $this->actingAs($this->p['hrd'])->get('/dashboard/payroll')->assertForbidden();
        $this->actingAs($this->p['hrd'])->get(route('dashboard.legal.index'))->assertForbidden();
        $this->actingAs($this->p['hrd'])->get(route('dashboard.it.index'))->assertForbidden();
    }

    public function test_owner_reaches_every_module_index(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        foreach (['work', 'work.tracker', 'work.meetings', 'kpi', 'contracts', 'payroll', 'budget', 'royalty', 'legal', 'it', 'it.changelog'] as $name) {
            $owner->get(route("dashboard.{$name}.index"))->assertOk();
        }
    }
}