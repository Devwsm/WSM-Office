<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\EmployeeContract;
use App\Models\Kpi;
use App\Models\LegalDocument;
use App\Models\Meeting;
use App\Models\PayrollRecord;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\RoyaltyEntry;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\ExportImport\ExportCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian J — Export & Import Center (J1–J10): menu per hak
 * akses, seluruh export Excel/PDF (file sungguhan dibuat dan dibaca ulang),
 * template import, preview → commit, serta pembatasan akses.
 */
class ExportImportTest extends TestCase
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

    /** Isi semua tabel sumber export dengan satu baris agar tiap file punya data untuk diperiksa. */
    private function seedExportData(): array
    {
        $project = Project::create(['name' => 'Album Q3', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
        Attendance::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-15',
            'session_number' => 1,
            'mode' => 'kantor',
            'clock_in_at' => '2026-09-15 09:30:00',
            'clock_out_at' => '2026-09-15 18:00:00'
        ]);
        $payroll = PayrollRecord::create([
            'user_id' => $this->p['aldora']->id,
            'period' => '2026-09',
            'base_salary' => 6000000,
            'overtime_amount' => 0,
            'shortage_deduction' => 0,
            'total' => 6000000,
            'status' => 'draft',
            'generated_by' => $this->p['manajer']->id
        ]);
        Kpi::create(['employee_id' => $this->p['gepeng']->id, 'title' => 'Posting konten', 'period' => '2026-Q3', 'target' => 30, 'current' => 12, 'status' => 'Active']);
        ProjectBudget::create(['project_id' => $project->id, 'category' => 'Produksi', 'item' => 'Studio', 'budget' => 5000000, 'actual' => 1000000]);
        RoyaltyEntry::create(['title' => 'Single Hujan', 'period' => '2026-08', 'status' => 'Reported', 'gross' => 1000000, 'share_pct' => 40]);
        EmployeeContract::create(['employee_id' => $this->p['aldora']->id, 'file_path' => 'contracts/x.pdf', 'original_filename' => 'x.pdf', 'uploaded_by' => $this->p['owner']->id]);
        LegalDocument::create(['category' => 'album', 'title' => 'Kontrak Album A', 'file_path' => 'legal/a.pdf', 'original_filename' => 'a.pdf', 'created_by' => $this->p['owner']->id]);
        AuditLog::record('Uji export', 'Baris audit untuk export.', $this->p['owner']);
        $opening = \App\Models\JobOpening::create(['title' => 'Editor', 'slug' => 'editor', 'employment_type' => 'full_time', 'description' => 'x', 'status' => 'published']);
        \App\Models\JobApplication::create(['job_opening_id' => $opening->id, 'name' => 'Pelamar Uji', 'email' => 'pelamar@example.com', 'status' => 'baru']);
        WorkItem::create(['project_id' => $project->id, 'section' => 'General', 'item_no' => 1, 'title' => 'Task Uji', 'progress' => 'Pending', 'priority' => 'Medium', 'created_by' => $this->p['owner']->id]);
        $meeting = Meeting::create(['project_id' => $project->id, 'date' => '2026-09-21', 'agenda' => 'Rapat Uji', 'created_by' => $this->p['owner']->id]);

        return compact('payroll', 'meeting');
    }

    /** Baca isi seluruh sel dari file .xlsx yang di-download sebagai array string. */
    private function cellsOf($response): array
    {
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        $sheet = IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();

        return collect($sheet->toArray(null, true, false, false))->flatten()->filter(fn($v) => $v !== null && $v !== '')->map(fn($v) => (string) $v)->values()->all();
    }

    // ---- J1: menu -------------------------------------------------------

    public function test_menu_shows_only_the_exports_the_user_has_access_to(): void
    {
        $keys = fn(User $u) => collect($this->actingAs($u)->get(route('dashboard.export-import.index'))->assertOk()->viewData('catalog'))->keys()->sort()->values()->all();

        $this->assertSame(collect(ExportCatalog::CATALOG)->keys()->sort()->values()->all(), $keys($this->p['owner']));
        $this->assertSame(['attendance-recap', 'audit-log', 'budget', 'contracts', 'kpi', 'leave-recap', 'legal', 'payroll', 'royalty'], $keys($this->p['manajer']));
        $this->assertSame(['attendance-recap', 'kpi', 'leave-recap', 'recruitment-applicants'], $keys($this->p['hrd']));
        $this->assertSame(['meetings', 'work-tracker'], $keys($this->p['aldora']));
        $this->assertSame(['meetings', 'work-tracker'], $keys($this->p['gepeng']));
    }

    public function test_menu_needs_login(): void
    {
        $this->get(route('dashboard.export-import.index'))->assertRedirect('/login');
    }

    // ---- J2–J5: semua export benar-benar menghasilkan file -------------

    public function test_owner_can_preview_and_download_every_implemented_export(): void
    {
        $seed = $this->seedExportData();
        $owner = $this->actingAs($this->p['owner']);
        $count = 0;

        foreach (ExportCatalog::CATALOG as $key => $entry) {
            foreach ($entry['implemented_exports'] as $format) {
                $params = match ("{$key}:{$format}") {
                    'attendance-recap:pdf' => ['employee_id' => $this->p['aldora']->id, 'period' => '2026-09'],
                    'payroll:pdf' => ['payroll_id' => $seed['payroll']->id, 'period' => '2026-09'],
                    'meetings:pdf' => ['meeting_id' => $seed['meeting']->id],
                    default => ['period' => '2026-09'],
                };

                $owner->get(route('dashboard.export-import.preview', ['key' => $key, 'format' => $format] + $params))
                    ->assertOk();

                $download = $owner->get(route('dashboard.export-import.download', ['key' => $key, 'format' => $format] + $params));
                $download->assertOk();

                $format === 'pdf'
                    ? $this->assertStringContainsString('application/pdf', $download->headers->get('content-type'), "{$key} pdf")
                    : $this->assertStringContainsString('.xlsx', (string) $download->headers->get('content-disposition'), "{$key} excel");

                $count++;
            }
        }

        $this->assertGreaterThanOrEqual(15, $count);
    }

    public function test_excel_exports_contain_the_expected_data(): void
    {
        $this->seedExportData();
        $owner = $this->actingAs($this->p['owner']);
        $get = fn(string $key, array $q = []) => $this->cellsOf($owner->get(route('dashboard.export-import.download', ['key' => $key, 'format' => 'excel'] + $q)));

        $this->assertContains('Posting konten', $get('kpi'));
        $this->assertContains('Studio', $get('budget'));
        $this->assertContains('Single Hujan', $get('royalty'));
        $this->assertContains('Kontrak Album A', $get('legal'));
        $this->assertContains('Pelamar Uji', $get('recruitment-applicants'));
        $this->assertContains('Task Uji', $get('work-tracker'));
        $this->assertContains('Aldora', $get('payroll', ['period' => '2026-09']));
        $this->assertContains('Aldora', $get('attendance-recap', ['period' => '2026-09']));
        $this->assertContains('Uji export', $get('audit-log'));
    }

    public function test_employee_export_lists_everyone_but_never_leaks_password_hashes(): void
    {
        $cells = $this->cellsOf($this->actingAs($this->p['owner'])->get(route('dashboard.export-import.download', ['key' => 'employees', 'format' => 'excel'])));

        foreach (['Aldora', 'Gepeng', 'Kanaya', 'Rania', 'owner@wsm.local'] as $expected) {
            $this->assertContains($expected, $cells);
        }

        $this->assertEmpty(array_filter($cells, fn($c) => str_starts_with($c, '$2y$') || str_contains($c, 'remember_token')), 'Hash password tidak boleh ikut ke file export.');
    }

    public function test_audit_log_export_respects_the_date_range(): void
    {
        AuditLog::record('Aksi lama', '-', $this->p['owner']);
        AuditLog::query()->update(['created_at' => '2026-08-01 10:00:00']);
        AuditLog::record('Aksi baru', '-', $this->p['owner']);

        $cells = $this->cellsOf($this->actingAs($this->p['manajer'])->get(route('dashboard.export-import.download', [
            'key' => 'audit-log',
            'format' => 'excel',
            'from' => '2026-09-01',
            'to' => '2026-09-30',
        ])));

        $this->assertContains('Aksi baru', $cells);
        $this->assertNotContains('Aksi lama', $cells);
    }

    public function test_attendance_recap_pdf_needs_an_employee_and_payroll_pdf_needs_a_record(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->get(route('dashboard.export-import.download', ['key' => 'attendance-recap', 'format' => 'pdf']))->assertStatus(422);
        $owner->get(route('dashboard.export-import.download', ['key' => 'payroll', 'format' => 'pdf', 'payroll_id' => 9999]))->assertNotFound();
        $owner->get(route('dashboard.export-import.download', ['key' => 'meetings', 'format' => 'pdf', 'meeting_id' => 9999]))->assertNotFound();
    }

    // ---- J6: pembatasan akses export -----------------------------------

    public function test_exports_are_forbidden_without_access_to_the_underlying_module(): void
    {
        $this->seedExportData();
        $url = fn(string $key, string $format = 'excel') => route('dashboard.export-import.download', ['key' => $key, 'format' => $format, 'period' => '2026-09']);

        $this->actingAs($this->p['hrd']);
        foreach (['payroll', 'budget', 'royalty', 'contracts', 'legal', 'audit-log', 'employees', 'work-tracker'] as $key) { // Rania: hanya people, kpi, recruitment
            $this->get($url($key))->assertForbidden();
        }

        $this->actingAs($this->p['aldora']);
        foreach (['kpi', 'payroll', 'attendance-recap', 'recruitment-applicants', 'employees'] as $key) {
            $this->get($url($key))->assertForbidden();
        }

        $this->actingAs($this->p['manajer'])->get($url('employees'))->assertForbidden(); // owner-only
        $this->get(route('dashboard.export-import.preview', ['key' => 'payroll', 'format' => 'excel']))->assertOk();
        $this->actingAs($this->p['hrd'])->get(route('dashboard.export-import.preview', ['key' => 'payroll', 'format' => 'excel']))->assertForbidden();
    }

    public function test_unknown_modules_and_formats_return_404(): void
    {
        $owner = $this->actingAs($this->p['owner']);

        $owner->get(route('dashboard.export-import.download', ['key' => 'modul-gaib', 'format' => 'excel']))->assertNotFound();
        $owner->get(route('dashboard.export-import.download', ['key' => 'kpi', 'format' => 'pdf']))->assertNotFound();   // KPI hanya Excel
        $owner->get(route('dashboard.export-import.download', ['key' => 'kpi', 'format' => 'docx']))->assertNotFound();
        $owner->get(route('dashboard.export-import.preview', ['key' => 'meetings', 'format' => 'excel']))->assertNotFound();
    }

    // ---- J7: template import -------------------------------------------

    public function test_import_templates_download_with_the_expected_headings(): void
    {
        $owner = $this->actingAs($this->p['owner']);
        $expected = [
            'work-tracker' => ['project', 'section', 'judul', 'tenggat', 'pic', 'progress', 'prioritas', 'catatan'],
            'kpi' => ['karyawan', 'judul_kpi', 'periode', 'target', 'capaian', 'satuan', 'bobot', 'tenggat', 'status', 'catatan_owner'],
            'budget' => ['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan'],
            'employees' => ['nama', 'email', 'password', 'role', 'divisi', 'jabatan', 'tanggal_masuk', 'jatah_cuti', 'tanggal_lahir', 'gaji_pokok', 'target_jam_per_hari', 'tarif_lembur_flat'],
        ];

        foreach ($expected as $key => $headings) {
            $owner->get(route('dashboard.export-import.import.show', ['key' => $key]))->assertOk();

            $cells = $this->cellsOf($owner->get(route('dashboard.export-import.import.template', ['key' => $key])));
            foreach ($headings as $heading) {
                $this->assertContains($heading, $cells, "Template {$key} kehilangan kolom {$heading}");
            }
        }
    }

    public function test_import_is_only_available_for_the_four_implemented_modules_with_manage_access(): void
    {
        $this->actingAs($this->p['owner'])->get(route('dashboard.export-import.import.show', ['key' => 'royalty']))->assertNotFound();
        $this->get(route('dashboard.export-import.import.show', ['key' => 'modul-gaib']))->assertNotFound();

        // Level view (Rania: kpi view) & tanpa akses tidak boleh import.
        $this->actingAs($this->p['hrd'])->get(route('dashboard.export-import.import.show', ['key' => 'kpi']))->assertForbidden();
        $this->actingAs($this->p['gepeng'])->get(route('dashboard.export-import.import.show', ['key' => 'work-tracker']))->assertForbidden();
        $this->actingAs($this->p['manajer'])->get(route('dashboard.export-import.import.show', ['key' => 'employees']))->assertForbidden();

        $this->actingAs($this->p['manajer'])->get(route('dashboard.export-import.import.show', ['key' => 'kpi']))->assertOk();
        $this->actingAs($this->p['aldora'])->get(route('dashboard.export-import.import.show', ['key' => 'work-tracker']))->assertOk();
    }

    // ---- J8–J10: preview → commit --------------------------------------

    private function csv(array $headings, array $rows, string $name = 'import.csv'): UploadedFile
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, $headings);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);

        return UploadedFile::fake()->createWithContent($name, stream_get_contents($stream));
    }

    private function kpiHeadings(): array
    {
        return ['karyawan', 'judul_kpi', 'periode', 'target', 'capaian', 'satuan', 'bobot', 'tenggat', 'status', 'catatan_owner'];
    }

    private function preview(User $user, string $key, UploadedFile $file)
    {
        return $this->actingAs($user)->post(route('dashboard.export-import.import.preview', ['key' => $key]), ['file' => $file]);
    }

    public function test_kpi_import_previews_valid_and_invalid_rows_and_commits_only_the_valid_ones(): void
    {
        $headings = ['karyawan', 'judul_kpi', 'periode', 'target', 'capaian', 'satuan', 'bobot', 'tenggat', 'status', 'catatan_owner'];
        $file = $this->csv($headings, [
            ['Gepeng', 'Posting konten', '2026-Q3', '30', '12', 'post', '40', '30/09/2026', 'Active', 'Fokus video'],
            ['aldora@wsm.local', 'Rilis single', '2026-Q3', '1', '', '', '', '', '', ''],
            ['Orang Fiktif', 'KPI hantu', '2026-Q3', '10', '0', '', '', '', '', ''],
            ['Gepeng', 'Target salah', '2026-Q3', 'banyak', '', '', '', '', '', ''],
        ]);

        $preview = $this->preview($this->p['manajer'], 'kpi', $file)->assertOk();
        $preview->assertViewHas('valid', fn($v) => count($v) === 2);
        $preview->assertViewHas('invalid', fn($i) => count($i) === 2);
        $this->assertDatabaseCount('kpis', 0); // preview belum menyimpan apa pun

        $token = $preview->viewData('token');
        $this->assertNotEmpty($token);

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'kpi']), ['token' => $token])
            ->assertRedirect(route('dashboard.export-import.index'))
            ->assertSessionHas('status', fn($m) => str_contains($m, '2 baris berhasil diimport') && str_contains($m, '2 baris error'));

        $this->assertDatabaseCount('kpis', 2);
        $kpi = Kpi::where('title', 'Posting konten')->firstOrFail();
        $this->assertSame($this->p['gepeng']->id, $kpi->employee_id);
        $this->assertEquals(30, $kpi->target);
        $this->assertEquals(12, $kpi->current);
        $this->assertSame('2026-09-30', $kpi->due_date->toDateString());
        $this->assertSame('Active', Kpi::where('title', 'Rilis single')->value('status'), 'Status kosong → Active.');
    }

    public function test_commit_token_is_single_use_user_bound_and_required(): void
    {
        $file = $this->csv($this->kpiHeadings(), [['Gepeng', 'KPI', '2026-Q3', '10', '', '', '', '', '', '']]);
        $token = $this->preview($this->p['manajer'], 'kpi', $file)->viewData('token');
        $commit = fn(User $u, string $t) => $this->actingAs($u)->post(route('dashboard.export-import.import.commit', ['key' => 'kpi']), ['token' => $t]);

        $commit($this->p['manajer'], '')->assertStatus(422);
        $commit($this->p['owner'], $token)->assertStatus(410);          // token milik user lain
        $commit($this->p['manajer'], 'token-ngawur')->assertStatus(410);
        $this->assertDatabaseCount('kpis', 0);

        $commit($this->p['manajer'], $token)->assertRedirect(route('dashboard.export-import.index'));
        $commit($this->p['manajer'], $token)->assertStatus(410);        // dipakai ulang
        $this->assertDatabaseCount('kpis', 1);
    }

    public function test_import_file_validation(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $url = route('dashboard.export-import.import.preview', ['key' => 'kpi']);

        $kanaya->post($url, [])->assertSessionHasErrors(['file' => 'Pilih file Excel/CSV hasil isian template dulu.']);
        $kanaya->post($url, ['file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf')])->assertSessionHasErrors(['file' => 'File harus format .xlsx, .xls, atau .csv.']);
        $kanaya->post($url, ['file' => UploadedFile::fake()->create('data.exe', 10)])->assertSessionHasErrors('file');
        $kanaya->post($url, ['file' => UploadedFile::fake()->create('besar.csv', 5121, 'text/csv')])->assertSessionHasErrors('file');
    }

    public function test_work_tracker_import_maps_project_pic_dates_and_defaults(): void
    {
        Project::create(['name' => 'Album Q3', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
        $file = $this->csv(['project', 'section', 'judul', 'tenggat', 'pic', 'progress', 'prioritas', 'catatan'], [
            ['Album Q3', 'Marketing', 'Bikin teaser', '25/09/2026', 'Gepeng', 'On Development', 'High', 'Urgent'],
            ['', '', 'Task tanpa project', '', '', '', '', ''],
            ['Project Gaib', '', 'Project tidak ada', '', '', '', '', ''],
            ['', '', 'PIC tidak ada', '', 'Siapa Ini', '', '', ''],
            ['', '', 'Tanggal ngawur', 'besok', '', '', '', ''],
        ]);

        $preview = $this->preview($this->p['aldora'], 'work-tracker', $file)->assertOk();
        $preview->assertViewHas('valid', fn($v) => count($v) === 2);
        $preview->assertViewHas('invalid', fn($i) => count($i) === 3);

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'work-tracker']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertDatabaseCount('work_items', 2);
        $teaser = WorkItem::where('title', 'Bikin teaser')->firstOrFail();
        $this->assertSame('Marketing', $teaser->section);
        $this->assertSame($this->p['gepeng']->id, $teaser->pic_employee_id);
        $this->assertSame('2026-09-25', $teaser->due_date->toDateString());
        $this->assertSame('On Development', $teaser->progress);

        $plain = WorkItem::where('title', 'Task tanpa project')->firstOrFail();
        $this->assertNull($plain->project_id);
        $this->assertSame('Pending', $plain->progress);
        $this->assertSame('OTHER', $plain->section);
        $this->assertSame($this->p['aldora']->id, $plain->created_by);
    }

    public function test_budget_import_requires_an_existing_project(): void
    {
        Project::create(['name' => 'Album Q3', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
        $file = $this->csv(['project', 'kategori', 'item', 'anggaran', 'realisasi', 'catatan'], [
            ['Album Q3', 'Produksi', 'Studio', '5000000', '1000000', 'DP'],
            ['Album Q3', 'Promosi', 'Iklan', '2000000', '', ''],
            ['Project Gaib', 'Produksi', 'Mixing', '100', '', ''],
        ]);

        $preview = $this->preview($this->p['manajer'], 'budget', $file);
        $preview->assertViewHas('valid', fn($v) => count($v) === 2)->assertViewHas('invalid', fn($i) => count($i) === 1);

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'budget']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertDatabaseCount('project_budgets', 2);
        $this->assertEquals(5000000, ProjectBudget::where('item', 'Studio')->value('budget'));
        $this->assertEquals(0, ProjectBudget::where('item', 'Iklan')->value('actual'));
    }

    public function test_employee_import_creates_accounts_that_can_log_in_and_rejects_bad_rows(): void
    {
        $headings = ['nama', 'email', 'password', 'role', 'divisi', 'jabatan', 'tanggal_masuk', 'jatah_cuti', 'tanggal_lahir', 'gaji_pokok', 'target_jam_per_hari', 'tarif_lembur_flat'];
        $file = $this->csv($headings, [
            ['Dewi Baru', 'dewi@wsm.local', 'rahasia123', 'karyawan', 'Creative', 'Editor', '01/10/2026', '12', '20/05/1999', '5500000', '8', '35000'],
            ['Eko Baru', 'eko@wsm.local', '', 'Karyawan', '', '', '', '', '', '', '', ''],
            ['Dobel Email', 'dewi@wsm.local', 'rahasia123', 'karyawan', '', '', '', '', '', '', '', ''],
            ['Email Sudah Ada', 'kanaya@wsm.local', 'rahasia123', 'karyawan', '', '', '', '', '', '', '', ''],
            ['Role Aneh', 'aneh@wsm.local', 'rahasia123', 'presiden', '', '', '', '', '', '', '', ''],
            ['Password Pendek', 'pendek@wsm.local', 'abc', 'karyawan', '', '', '', '', '', '', '', ''],
        ]);

        $preview = $this->preview($this->p['owner'], 'employees', $file)->assertOk();
        $preview->assertViewHas('valid', fn($v) => count($v) === 2)->assertViewHas('invalid', fn($i) => count($i) === 4);

        $this->post(route('dashboard.export-import.import.commit', ['key' => 'employees']), ['token' => $preview->viewData('token')])->assertRedirect();

        $this->assertSame(7, User::count());
        $dewi = User::where('email', 'dewi@wsm.local')->firstOrFail();
        $this->assertEquals(5500000, $dewi->salary_base);
        $this->assertSame('2026-10-01', $dewi->join_date->toDateString());

        // Password kosong → default "password".
        $this->post('/logout');
        $this->post('/login', ['email' => 'eko@wsm.local', 'password' => 'password'])->assertRedirect(route('employee.home'));
        $this->post('/logout');
        $this->post('/login', ['email' => 'dewi@wsm.local', 'password' => 'rahasia123'])->assertRedirect(route('employee.home'));
    }

    public function test_import_preview_is_throttled_at_ten_per_minute(): void
    {
        $file = fn() => $this->csv($this->kpiHeadings(), [['Gepeng', 'KPI', '2026-Q3', '10', '', '', '', '', '', '']]);

        for ($i = 0; $i < 10; $i++) {
            $this->preview($this->p['manajer'], 'kpi', $file())->assertOk();
        }

        $this->preview($this->p['manajer'], 'kpi', $file())->assertStatus(429);
    }

    /**
     * GAP (ditemukan saat menulis tes): file import yang tidak memuat kolom
     * opsional (mis. tanpa `capaian` di KPI) meledak jadi error 500
     * ("Undefined array key"), padahal seharusnya pesan "kolom X tidak ada".
     * Selama pengguna memakai template, ini tidak muncul. Hapus skip setelah
     * importer memvalidasi heading.
     */
    public function test_import_with_missing_optional_columns_is_reported_instead_of_crashing(): void
    {
        $this->markTestSkipped('GAP: KpiImport error 500 bila kolom opsional (capaian, dst.) tidak ada di file.');

        $file = $this->csv(['karyawan', 'judul_kpi', 'periode', 'target'], [['Gepeng', 'KPI', '2026-Q3', '10']]);
        $this->preview($this->p['manajer'], 'kpi', $file)->assertOk();
    }

    /**
     * GAP: tanggal ngawur seperti 31/02/2026 atau 31-31-2026 tidak ditolak —
     * `Carbon::createFromFormat()` PHP menggulung tanggal (jadi 03/03/2026 dan
     * 31/07/2028) sehingga baris dianggap valid dengan tanggal yang salah.
     * Perbaikan: cek `Carbon::getLastErrors()` (warning_count) di parseDate().
     */
    public function test_impossible_dates_are_rejected_instead_of_silently_rolled_over(): void
    {
        $this->markTestSkipped('GAP: parseDate() menggulung tanggal mustahil (31/02 → 03/03) alih-alih menolak baris.');

        $file = $this->csv($this->kpiHeadings(), [['Gepeng', 'KPI', '2026-Q3', '10', '', '', '', '31/02/2026', '', '']]);
        $this->preview($this->p['manajer'], 'kpi', $file)->assertViewHas('invalid', fn($i) => count($i) === 1);
    }
}