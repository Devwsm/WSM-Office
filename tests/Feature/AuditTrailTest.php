<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Kpi;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\User;
use App\Models\WorkItem;
use App\Support\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Audit Log lengkap (2026-10-09): perubahan data tercatat dengan nilai lama → baru,
 * login berhasil/gagal tercatat, aksi tanpa catatan khusus tetap masuk lewat
 * middleware pengaman, dan halaman Audit Log bisa difilter.
 */
class AuditTrailTest extends TestCase
{
    use CreatesWsmFixtures;
    use RefreshDatabase;

    /** @var array{owner:User,manajer:User,hrd:User,aldora:User,gepeng:User} */
    private array $p;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeWorkday();
        $this->officeSetting();
        Storage::fake('local');
        $this->p = $this->company();
        $this->project = Project::create(['name' => 'Album Q4', 'priority' => 'High', 'status' => 'On Development', 'created_by' => $this->p['owner']->id]);
    }

    private function item(array $o = []): WorkItem
    {
        return WorkItem::create(array_merge([
            'project_id' => $this->project->id,
            'section' => 'CONTRACT',
            'item_no' => 1,
            'title' => 'Approval lirik',
            'due_date' => '2026-09-24',
            'progress' => 'Pending',
            'priority' => 'Medium',
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    private function lastLog(string $action): AuditLog
    {
        return AuditLog::query()->where('action', $action)->latest('id')->firstOrFail();
    }

    // --- Work Tracker (edit satuan) ---

    public function test_perubahan_progress_task_mencatat_nilai_lama_dan_baru(): void
    {
        $item = $this->item();

        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.tracker.items.progress', $item), ['progress' => 'On Development'])->assertOk();

        $log = $this->lastLog('Progress task diubah');
        $this->assertStringContainsString('Approval lirik', $log->detail);
        $this->assertStringContainsString('Progress: Pending → On Development', $log->detail);
        $this->assertSame($this->p['aldora']->id, $log->actor_id);
        $this->assertSame('Work Tracker', $log->area);
        $this->assertNotNull($log->ip_address);
    }

    public function test_edit_cepat_judul_dan_pic_mencatat_nama_bukan_id(): void
    {
        $item = $this->item();
        $aldora = $this->p['aldora'];

        $this->actingAs($aldora)->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'title', 'value' => 'Approval lirik v2'])->assertOk();
        $this->assertStringContainsString('Judul: Approval lirik → Approval lirik v2', $this->lastLog('Task diubah (edit cepat)')->detail);

        $this->actingAs($aldora)->patchJson(route('dashboard.work.tracker.items.field', $item), ['field' => 'pic_employee_id', 'value' => $this->p['gepeng']->id])->assertOk();
        $detail = AuditLog::query()->where('action', 'Task diubah (edit cepat)')->latest('id')->value('detail');
        $this->assertStringContainsString('PIC: — → Gepeng', $detail);
    }

    public function test_catatan_task_dan_hapus_task_tercatat(): void
    {
        $item = $this->item();

        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.tracker.items.note', $item), ['notes' => 'Tunggu revisi label'])->assertOk();
        $this->assertStringContainsString('Catatan: — → Tunggu revisi label', $this->lastLog('Catatan task diubah')->detail);

        $this->actingAs($this->p['aldora'])->delete(route('dashboard.work.tracker.items.destroy', $item))->assertRedirect();
        $this->assertStringContainsString('Approval lirik', $this->lastLog('Task dihapus')->detail);
    }

    // --- KPI & Budget ---

    public function test_kpi_dibuat_diubah_dan_dihapus_tercatat(): void
    {
        $kanaya = $this->actingAs($this->p['manajer']);
        $payload = ['employee_id' => $this->p['aldora']->id, 'title' => 'Posting konten', 'period' => 'Oktober 2026', 'target' => 30, 'current' => 10, 'status' => 'Active'];

        $kanaya->post(route('dashboard.kpi.store'), $payload)->assertRedirect();
        $this->assertStringContainsString('Karyawan: Aldora', $this->lastLog('KPI ditambahkan')->detail);

        $kpi = Kpi::sole();
        $kanaya->patch(route('dashboard.kpi.update', $kpi), array_merge($payload, ['current' => 25]))->assertRedirect();
        $detail = $this->lastLog('KPI diperbarui')->detail;
        $this->assertStringContainsString('Capaian: 10 → 25', $detail);
        $this->assertStringNotContainsString('Target', $detail, 'kolom yang tidak berubah tidak ikut ditulis');

        $kanaya->delete(route('dashboard.kpi.destroy', $kpi))->assertRedirect();
        $this->assertStringContainsString('Posting konten', $this->lastLog('KPI dihapus')->detail);
    }

    public function test_edit_cepat_budget_mencatat_perubahan_angka(): void
    {
        $line = ProjectBudget::create(['project_id' => $this->project->id, 'category' => 'Gaji Tim', 'item' => 'Mixing', 'budget' => 1000000, 'actual' => 0, 'updated_by' => $this->p['owner']->id]);

        $this->actingAs($this->p['manajer'])->patchJson(route('dashboard.budget.field', $line), ['field' => 'actual', 'value' => 750000])->assertOk();

        $detail = $this->lastLog('Budget item diubah (edit cepat)')->detail;
        $this->assertStringContainsString('Mixing', $detail);
        $this->assertStringContainsString('Actual: 0 → 750000', $detail);
    }

    // --- Pengajuan karyawan, password, auto-close ---

    public function test_pengajuan_lembur_dan_pembatalannya_tercatat(): void
    {
        $aldora = $this->actingAs($this->p['aldora']);
        $aldora->post(route('employee.overtime.store'), ['date' => '2026-09-22', 'reason' => 'Mengejar deadline rilis'])->assertRedirect();
        $this->assertStringContainsString('Mengejar deadline rilis', $this->lastLog('Pengajuan lembur dikirim')->detail);

        $overtime = \App\Models\OvertimeRequest::sole();
        $aldora->post(route('employee.overtime.cancel', $overtime), ['cancellation_reason' => 'Batal lembur'])->assertRedirect();
        $this->assertStringContainsString('Batal lembur', $this->lastLog('Pengajuan lembur dibatalkan pemohon')->detail);
    }

    public function test_ganti_password_tercatat_tanpa_menyimpan_passwordnya(): void
    {
        $this->actingAs($this->p['aldora'])->patch('/app/profile/password', [
            'current_password' => 'password',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertSessionHasNoErrors();

        $log = $this->lastLog('Password diganti');
        $this->assertStringNotContainsString('passwordbaru123', $log->detail);
        $this->assertSame($this->p['aldora']->id, $log->actor_id);
    }

    public function test_sesi_lupa_pulang_yang_ditutup_otomatis_tercatat_sebagai_sistem(): void
    {
        Attendance::create(['user_id' => $this->p['aldora']->id, 'date' => '2026-09-18', 'session_number' => 1, 'mode' => 'kantor', 'clock_in_at' => '2026-09-18 09:30:00']);

        $this->actingAs($this->p['aldora'])->get('/app/home')->assertOk();

        $log = $this->lastLog('Sesi absen ditutup otomatis');
        $this->assertNull($log->actor_id);
        $this->assertSame('System', $log->actor_label);
        $this->assertStringContainsString('Aldora', $log->detail);
    }

    // --- Login ---

    public function test_login_berhasil_dan_gagal_tercatat(): void
    {
        $this->post('/login', ['email' => 'aldora@wsm.local', 'password' => 'salah'])->assertSessionHasErrors('email');
        $failed = $this->lastLog('Login gagal');
        $this->assertStringContainsString('aldora@wsm.local', $failed->detail);
        $this->assertStringContainsString('password salah', $failed->detail);
        $this->assertSame('Login', $failed->area);

        $this->post('/login', ['email' => 'tidakada@wsm.local', 'password' => 'x'])->assertSessionHasErrors('email');
        $this->assertStringContainsString('email tidak terdaftar', AuditLog::query()->where('action', 'Login gagal')->latest('id')->value('detail'));

        $this->post('/login', ['email' => 'aldora@wsm.local', 'password' => 'password']);
        $ok = $this->lastLog('Login berhasil');
        $this->assertSame($this->p['aldora']->id, $ok->actor_id);
        $this->assertSame(0, AuditLog::query()->where('action', 'Perubahan data (tidak terinci)')->count(), 'login tidak dicatat ganda oleh jaring pengaman');
    }

    // --- Jaring pengaman ---

    public function test_aksi_berhasil_tanpa_catatan_khusus_tetap_masuk_log_generik(): void
    {
        Route::middleware('web')->post('/_uji/tanpa-catatan', fn() => back()->with('status', 'ok'))->name('uji.tanpa-catatan');
        Route::middleware('web')->post('/_uji/gagal-validasi', fn() => back()->withErrors(['x' => 'salah']))->name('uji.gagal-validasi');
        Route::middleware('web')->post('/_uji/ditolak', fn() => back()->with('error', 'tidak bisa'))->name('uji.ditolak');
        Route::middleware('web')->post('/_uji/error', fn() => abort(403))->name('uji.error');

        $aldora = $this->actingAs($this->p['aldora']);

        $aldora->post('/_uji/gagal-validasi');
        $aldora->post('/_uji/ditolak');
        $aldora->post('/_uji/error');
        $this->assertSame(0, AuditLog::count(), 'validasi gagal, aksi ditolak, dan error tidak dicatat');

        $aldora->post('/_uji/tanpa-catatan')->assertRedirect();
        $log = $this->lastLog('Perubahan data (tidak terinci)');
        $this->assertStringContainsString('uji.tanpa-catatan', $log->detail);
        $this->assertSame($this->p['aldora']->id, $log->actor_id);
    }

    public function test_route_yang_didaftarkan_sebagai_pengecualian_tidak_dicatat(): void
    {
        // Absen masuk/pulang tidak masuk Audit Log (catatan absensinya sendiri sudah menjadi jejak).
        $this->actingAs($this->p['aldora'])->post(route('presence.ping'))->assertNoContent();

        $this->assertSame(0, AuditLog::count());
    }

    // --- Halaman Audit Log ---

    public function test_halaman_audit_log_bisa_difilter_dan_menampilkan_ip_dan_area(): void
    {
        $this->actingAs($this->p['aldora'])->patchJson(route('dashboard.work.tracker.items.progress', $this->item()), ['progress' => 'Done'])->assertOk();
        AuditLog::record('Contoh aksi sistem', 'Dicatat otomatis.', 'System', 'Absensi');

        $owner = $this->actingAs($this->p['owner']);

        $all = $owner->get(route('dashboard.it.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Progress task diubah', $all);
        $this->assertStringContainsString('Work Tracker', $all);
        $this->assertStringContainsString('IP ', $all);

        $byActor = $owner->get(route('dashboard.it.index', ['actor' => $this->p['aldora']->id]))->getContent();
        $this->assertStringContainsString('Progress task diubah', $byActor);
        $this->assertStringNotContainsString('Contoh aksi sistem', $byActor);

        $byArea = $owner->get(route('dashboard.it.index', ['area' => 'Absensi']))->getContent();
        $this->assertStringContainsString('Contoh aksi sistem', $byArea);
        $this->assertStringNotContainsString('Progress task diubah', $byArea);

        $system = $owner->get(route('dashboard.it.index', ['actor' => 'system']))->getContent();
        $this->assertStringContainsString('Contoh aksi sistem', $system);

        $future = $owner->get(route('dashboard.it.index', ['from' => '2099-01-01']))->getContent();
        $this->assertStringContainsString('Gak ada log yang cocok dengan filter', $future);

        $owner->get(route('dashboard.it.index', ['from' => 'bukan-tanggal', 'actor' => 'abc']))->assertOk();
    }

    // --- Pembantu Audit ---

    public function test_audit_changes_mengabaikan_beda_format_angka_dan_hanya_menulis_yang_berubah(): void
    {
        $line = ProjectBudget::create(['project_id' => $this->project->id, 'category' => 'Gaji Tim', 'item' => 'Mixing', 'budget' => 1000000, 'actual' => 5, 'updated_by' => $this->p['owner']->id]);
        $line = $line->fresh(); // budget kembali sebagai "1000000.00" dari database

        $same = Audit::changes($line, ['budget' => 1000000, 'item' => 'Mixing'], Audit::labels('budget'));
        $this->assertSame('tanpa perubahan pada isian utama', $same);

        $changed = Audit::changes($line, ['budget' => 1500000, 'item' => 'Mixing'], Audit::labels('budget'));
        $this->assertSame('Budget: 1000000 → 1500000', $changed);
    }
}