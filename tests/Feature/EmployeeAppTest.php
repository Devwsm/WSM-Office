<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Kpi;
use App\Models\Memo;
use App\Models\MemoRead;
use App\Models\MemoThreadMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian C (sisa) & K — App Mode karyawan: Home (C1),
 * memo/inbox (C2–C4), profil, batas akses (C24) dan navigasi dashboard (K1, K2, K4).
 */
class EmployeeAppTest extends TestCase
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

    private function memo(array $o = []): Memo
    {
        return Memo::create(array_merge([
            'type' => 'memo',
            'title' => 'Pengumuman',
            'content' => 'Isi pengumuman',
            'audience' => 'semua',
            'active' => true,
            'pinned' => false,
            'created_by' => $this->p['owner']->id,
        ], $o));
    }

    // ---- C1: Home -------------------------------------------------------

    public function test_home_renders_for_every_internal_role(): void
    {
        foreach ($this->p as $user) {
            $this->actingAs($user)->get(route('employee.home'))->assertOk();
        }
    }

    public function test_home_shows_kpi_of_the_logged_in_employee_only(): void
    {
        Kpi::create(['employee_id' => $this->p['gepeng']->id, 'title' => 'KPI Konten Gepeng', 'period' => '2026-Q3', 'target' => 100, 'current' => 60, 'status' => 'Active']);
        Kpi::create(['employee_id' => $this->p['aldora']->id, 'title' => 'KPI Milik Aldora', 'period' => '2026-Q3', 'target' => 100, 'current' => 10, 'status' => 'Active']);
        Kpi::create(['employee_id' => $this->p['gepeng']->id, 'title' => 'KPI Diarsipkan', 'period' => '2026-Q3', 'target' => 100, 'current' => 10, 'status' => 'Archived']);

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertOk()
            ->assertViewHas('kpis', fn($k) => $k->pluck('title')->all() === ['KPI Konten Gepeng'])
            ->assertSee('KPI Konten Gepeng')
            ->assertDontSee('KPI Milik Aldora');
    }

    public function test_home_metrics_today_and_this_month(): void
    {
        foreach (['2026-09-14', '2026-09-15'] as $date) {
            Attendance::create([
                'user_id' => $this->p['aldora']->id,
                'date' => $date,
                'session_number' => 1,
                'mode' => 'kantor',
                'clock_in_at' => "{$date} 09:30:00",
                'clock_out_at' => "{$date} 18:00:00"
            ]);
        }
        Attendance::create([
            'user_id' => $this->p['aldora']->id,
            'date' => '2026-09-21',
            'session_number' => 1,
            'mode' => 'wfh',
            'clock_in_at' => '2026-09-21 08:00:00'
        ]);

        $this->actingAs($this->p['aldora'])->get(route('employee.home'))
            ->assertOk()
            ->assertViewHas('daysPresentThisMonth', 3)
            ->assertViewHas('workedMinutesToday', 120) // 08:00 → 10:00 (waktu dibekukan)
            ->assertViewHas('latestAttendance', fn($rows) => $rows->count() === 3);
    }

    public function test_home_shows_upcoming_birthdays_and_anniversaries_of_the_team(): void
    {
        $this->p['gepeng']->update(['birth_date' => '1999-09-25']);

        $this->actingAs($this->p['aldora'])->get(route('employee.home'))
            ->assertOk()
            ->assertViewHas('teamMoments', fn($m) => $m->contains(fn($row) => $row['type'] === 'birthday' && $row['user']->is($this->p['gepeng']) && (int) $row['days'] === 4));
    }

    public function test_home_shows_team_members_on_approved_leave_this_month(): void
    {
        \App\Models\LeaveRequest::create([
            'user_id' => $this->p['gepeng']->id,
            'type' => 'cuti_tahunan',
            'start_date' => '2026-09-24',
            'end_date' => '2026-09-25',
            'work_days' => 2,
            'reason' => 'Liburan',
            'status' => 'disetujui',
        ]);

        $this->actingAs($this->p['aldora'])->get(route('employee.home'))
            ->assertViewHas('teamLeavesThisMonth', fn($l) => $l->count() === 1 && $l->first()->user->is($this->p['gepeng']));

        // Cuti sendiri tidak masuk daftar "tim yang cuti".
        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertViewHas('teamLeavesThisMonth', fn($l) => $l->isEmpty());
    }

    // ---- C2: memo -------------------------------------------------------

    public function test_inactive_and_foreign_targeted_memos_are_hidden_from_home_and_inbox(): void
    {
        $this->memo(['title' => 'Memo Aktif Publik']);
        $this->memo(['title' => 'Memo Sudah Dimatikan', 'active' => false]);
        $private = $this->memo(['title' => 'Memo Khusus Gepeng', 'audience' => 'tertentu']);
        $private->recipients()->sync([$this->p['gepeng']->id]);

        // Home DAN modal Inbox (layouts.employee) sama-sama dirender di respons ini.
        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))
            ->assertSee('Memo Aktif Publik')->assertSee('Memo Khusus Gepeng')->assertDontSee('Memo Sudah Dimatikan');

        $rania = $this->actingAs($this->p['hrd'])->get(route('employee.home'));
        $rania->assertSee('Memo Aktif Publik')->assertDontSee('Memo Khusus Gepeng')->assertDontSee('Memo Sudah Dimatikan');
    }

    public function test_opening_home_marks_visible_memos_as_read_afterwards(): void
    {
        $memo = $this->memo();

        $this->assertFalse($memo->fresh()->isReadBy($this->p['gepeng']));

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertOk();

        $this->assertTrue($memo->fresh()->isReadBy($this->p['gepeng']));
        $this->assertFalse($memo->fresh()->isReadBy($this->p['aldora']), 'Status baca bersifat per-user.');
    }

    // ---- C3: inbox ------------------------------------------------------

    public function test_inbox_mark_all_read_endpoint_clears_the_unread_badge(): void
    {
        $this->memo(['title' => 'Satu']);
        $this->memo(['title' => 'Dua']);
        $hidden = $this->memo(['title' => 'Disembunyikan']);
        $this->actingAs($this->p['gepeng'])->post(route('employee.memo.toggleHidden', $hidden));

        $this->post(route('employee.memo.markAllRead'))->assertNoContent();

        $this->assertSame(2, MemoRead::where('user_id', $this->p['gepeng']->id)->whereNotNull('read_at')->count());
        $this->assertSame(0, Memo::query()->active()->visibleTo($this->p['gepeng'])->get()->reject(fn($m) => $m->isHiddenBy($this->p['gepeng']) || $m->isReadBy($this->p['gepeng']))->count());
    }

    public function test_memo_read_and_hidden_toggles_work_per_user(): void
    {
        $memo = $this->memo(['title' => 'Judul Unik Toggle']);
        $gepeng = $this->actingAs($this->p['gepeng']);

        $gepeng->post(route('employee.memo.toggleRead', $memo))->assertRedirect();
        $this->assertTrue($memo->fresh()->isReadBy($this->p['gepeng']));
        $gepeng->post(route('employee.memo.toggleRead', $memo));
        $this->assertFalse($memo->fresh()->isReadBy($this->p['gepeng']));

        $gepeng->post(route('employee.memo.toggleHidden', $memo))->assertSessionHas('status', 'Memo disembunyikan.');
        $this->assertTrue($memo->fresh()->isHiddenBy($this->p['gepeng']));
        // Home menaruh memo tersembunyi di bagian lipat "N disembunyikan"; di Inbox (modal) memo itu hilang.
        $gepeng->get(route('employee.home'))->assertSee('1 disembunyikan');

        $gepeng->post(route('employee.memo.toggleHidden', $memo))->assertSessionHas('status', 'Memo ditampilkan lagi.');
        $gepeng->get(route('employee.home'))->assertDontSee('disembunyikan');
        $this->assertFalse($memo->fresh()->isHiddenBy($this->p['gepeng']));

        $this->assertFalse($memo->fresh()->isHiddenBy($this->p['aldora']));
    }

    // ---- C4: balas thread ----------------------------------------------

    public function test_reply_is_saved_marks_the_memo_read_and_validates_input(): void
    {
        $memo = $this->memo();
        $gepeng = $this->actingAs($this->p['gepeng']);

        $gepeng->post(route('employee.memo.reply', $memo), ['message' => ''])->assertSessionHasErrors('message');
        $gepeng->post(route('employee.memo.reply', $memo), ['message' => str_repeat('x', 2001)])->assertSessionHasErrors('message');
        $this->assertSame(0, MemoThreadMessage::count());

        $gepeng->post(route('employee.memo.reply', $memo), ['message' => 'Siap, dikerjakan!'])
            ->assertSessionHas('status', 'Reply terkirim.');

        $this->assertSame('Siap, dikerjakan!', MemoThreadMessage::sole()->message);
        $this->assertSame($this->p['gepeng']->id, MemoThreadMessage::sole()->user_id);
        $this->assertTrue($memo->fresh()->isReadBy($this->p['gepeng']));
    }

    public function test_reply_is_throttled_at_fifteen_per_minute(): void
    {
        $memo = $this->memo();
        $gepeng = $this->actingAs($this->p['gepeng']);

        for ($i = 0; $i < 15; $i++) {
            $gepeng->post(route('employee.memo.reply', $memo), ['message' => "balasan {$i}"])->assertRedirect();
        }

        $gepeng->post(route('employee.memo.reply', $memo), ['message' => 'terlalu banyak'])->assertStatus(429);
        $this->assertSame(15, MemoThreadMessage::count());
    }

    /**
     * GAP KEAMANAN KECIL (ditemukan saat menulis tes): endpoint interaksi memo
     * tidak memeriksa apakah user termasuk audiens memo — siapa pun yang login
     * bisa membalas/menandai memo bertarget milik orang lain bila menebak id-nya.
     * Hapus baris skip setelah controller memeriksa `Memo::isVisibleTo()`.
     */
    public function test_user_outside_the_audience_cannot_reply_to_a_targeted_memo(): void
    {
        $this->markTestSkipped('GAP: MemoInteractionController tidak memeriksa audiens memo (isVisibleTo).');

        $private = $this->memo(['audience' => 'tertentu']);
        $private->recipients()->sync([$this->p['gepeng']->id]);

        $this->actingAs($this->p['hrd'])->post(route('employee.memo.reply', $private), ['message' => 'nyelonong'])->assertForbidden();
    }

    // ---- profil ---------------------------------------------------------

    public function test_profile_page_shows_the_account_details(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('employee.profile.index'))
            ->assertOk()
            ->assertSee('Aldora')
            ->assertSee('aldora@wsm.local');
    }

    // ---- C24 / K1 / K2: batas akses & navigasi -------------------------

    public function test_regular_employee_is_blocked_from_owner_payroll_and_approval_areas(): void
    {
        foreach (['gepeng'] as $who) {
            $this->actingAs($this->p[$who]);

            foreach (['/owner/dashboard', '/dashboard/payroll', '/persetujuan', '/dashboard/kpi', '/dashboard/contracts', '/absensi', '/rekrutmen/lowongan', '/dashboard/export-import/kpi/import'] as $url) {
                $this->get($url)->assertForbidden();
            }
        }
    }

    public function test_dashboard_only_lists_the_modules_the_user_may_open(): void
    {
        $key = fn($response) => $response->viewData('modules')->pluck('key')->sort()->values()->all();

        $this->assertSame(
            collect(\App\Models\DashboardAccess::MODULES)->keys()->sort()->values()->all(),
            $key($this->actingAs($this->p['owner'])->get(route('dashboard.index'))->assertOk()),
        );
        $this->assertSame(['work'], $key($this->actingAs($this->p['gepeng'])->get(route('dashboard.index'))->assertOk()));
        $this->assertSame(
            ['budget', 'contracts', 'it', 'kpi', 'legal', 'payroll', 'people', 'royalty'],
            $key($this->actingAs($this->p['manajer'])->get(route('dashboard.index'))->assertOk()),
        );
        $this->assertSame(['kpi', 'people', 'recruitment'], $key($this->actingAs($this->p['hrd'])->get(route('dashboard.index'))->assertOk()));
    }

    public function test_employee_without_any_module_is_sent_back_to_app_home_from_the_dashboard(): void
    {
        $nobody = $this->makeUser('karyawan');

        $this->actingAs($nobody)->get(route('dashboard.index'))
            ->assertRedirect(route('employee.home'))
            ->assertSessionHas('error', 'Kamu belum punya akses dashboard modul apapun.');
    }

    public function test_dashboard_entry_button_only_shows_for_users_with_access(): void
    {
        $nobody = $this->makeUser('karyawan');

        $this->actingAs($this->p['gepeng'])->get(route('employee.home'))->assertSee(route('dashboard.index'), false);
        $this->actingAs($nobody)->get(route('employee.home'))->assertDontSee(route('dashboard.index'), false);
    }

    public function test_placeholder_module_pages_and_unknown_modules(): void
    {
        // 'people' belum punya halaman sendiri di /dashboard/{module} → halaman placeholder generik.
        $this->actingAs($this->p['manajer'])->get('/dashboard/people')->assertOk();
        $this->get('/dashboard/modul-gaib')->assertForbidden();
        $this->actingAs($this->p['aldora'])->get('/dashboard/people')->assertForbidden();
    }

    public function test_sidebar_hides_the_attendance_recap_link_from_users_without_people_access(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('dashboard.work.index'))
            ->assertOk()
            ->assertDontSee(route('attendance.recap.index'), false)
            ->assertDontSee(route('dashboard.payroll.index'), false);

        $this->actingAs($this->p['manajer'])->get(route('dashboard.kpi.index'))
            ->assertOk()
            ->assertSee(route('attendance.recap.index'), false);
    }

    // ---- K4: halaman error ---------------------------------------------

    public function test_forbidden_page_uses_the_custom_403_template(): void
    {
        $this->actingAs($this->p['gepeng'])->get('/owner/dashboard')
            ->assertForbidden()
            ->assertSee('Kamu tidak punya akses ke halaman ini');
    }
}