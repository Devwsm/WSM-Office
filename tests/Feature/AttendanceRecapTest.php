<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesWsmFixtures;
use Tests\TestCase;

/**
 * Checklist manual bagian E — Rekap Absensi (modul `people`), E1–E5.
 * (Akses foto selfie sudah dicakup PrivateFileAccessTest.)
 */
class AttendanceRecapTest extends TestCase
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

    private function attendance(User $user, string $date, string $in, ?string $out, string $mode = 'kantor'): Attendance
    {
        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'session_number' => 1,
            'mode' => $mode,
            'clock_in_at' => "{$date} {$in}:00",
            'clock_out_at' => $out ? "{$date} {$out}:00" : null,
        ]);
    }

    // ---- E1: cakupan per akun ------------------------------------------

    public function test_owner_and_hrd_see_all_employees_manager_sees_self_and_team_only(): void
    {
        $ids = fn($response) => $response->viewData('rows')->pluck('user.id')->sort()->values()->all();

        $all = collect($this->p)->pluck('id')->sort()->values()->all();
        $team = collect([$this->p['manajer'], $this->p['aldora'], $this->p['gepeng']])->pluck('id')->sort()->values()->all();

        $this->assertSame($all, $ids($this->actingAs($this->p['owner'])->get(route('attendance.recap.index'))->assertOk()));
        $this->assertSame($all, $ids($this->actingAs($this->p['hrd'])->get(route('attendance.recap.index'))->assertOk()));
        $this->assertSame($team, $ids($this->actingAs($this->p['manajer'])->get(route('attendance.recap.index'))->assertOk()));
    }

    public function test_employee_without_people_module_gets_403(): void
    {
        $this->actingAs($this->p['aldora'])->get(route('attendance.recap.index'))->assertForbidden();
        $this->actingAs($this->p['gepeng'])->get(route('attendance.recap.show', $this->p['gepeng']))->assertForbidden();
    }

    public function test_employee_who_is_granted_people_only_sees_own_data(): void
    {
        $this->grant($this->p['aldora'], 'people', 'view');

        $this->actingAs($this->p['aldora'])->get(route('attendance.recap.index'))
            ->assertOk()
            ->assertViewHas('rows', fn($rows) => $rows->count() === 1 && $rows->first()['user']->is($this->p['aldora']));
    }

    public function test_daily_summary_counts_statuses_correctly(): void
    {
        $this->attendance($this->p['aldora'], '2026-09-21', '09:30', '18:00');           // hadir
        $this->attendance($this->p['gepeng'], '2026-09-21', '10:30', '18:30', 'wfh');    // terlambat + wfh
        LeaveRequest::create([
            'user_id' => $this->p['hrd']->id,
            'type' => 'izin_sakit',
            'start_date' => '2026-09-21',
            'end_date' => '2026-09-21',
            'work_days' => 1,
            'reason' => 'Demam',
            'status' => 'disetujui',
        ]);

        $this->actingAs($this->p['owner'])->get(route('attendance.recap.index'))
            ->assertOk()
            ->assertViewHas('isToday', true)
            ->assertViewHas('summary', [
                'total' => 5,
                'hadir' => 2,
                'terlambat' => 1,
                'wfh' => 1,
                'izin_cuti' => 1,
                'belum_absen' => 2, // Owner & Kanaya
            ]);
    }

    public function test_recap_date_filter_and_invalid_date_fallback(): void
    {
        $this->attendance($this->p['aldora'], '2026-09-14', '09:30', '18:00');

        $this->actingAs($this->p['owner'])->get(route('attendance.recap.index', ['tanggal' => '2026-09-14']))
            ->assertOk()
            ->assertViewHas('isToday', false)
            ->assertViewHas('summary', fn($s) => $s['hadir'] === 1);

        $this->get(route('attendance.recap.index', ['tanggal' => 'bukan-tanggal']))
            ->assertOk()
            ->assertViewHas('date', '2026-09-21');
    }

    // ---- E2: detail per orang ------------------------------------------

    public function test_employee_detail_shows_the_selected_month_and_approved_leaves(): void
    {
        $this->attendance($this->p['aldora'], '2026-09-10', '09:30', '18:00');
        $this->attendance($this->p['aldora'], '2026-08-11', '09:30', '18:00');
        LeaveRequest::create([
            'user_id' => $this->p['aldora']->id,
            'type' => 'izin_pribadi',
            'start_date' => '2026-09-16',
            'end_date' => '2026-09-16',
            'work_days' => 1,
            'reason' => 'Urusan keluarga',
            'status' => 'disetujui',
        ]);

        $this->actingAs($this->p['owner'])->get(route('attendance.recap.show', $this->p['aldora']))
            ->assertOk()
            ->assertViewHas('rows', fn($r) => $r->count() === 1 && $r->first()->date->toDateString() === '2026-09-10')
            ->assertViewHas('leaves', fn($l) => $l->count() === 1)
            ->assertViewHas('prevMonth', '2026-08');

        $this->get(route('attendance.recap.show', [$this->p['aldora'], 'bulan' => '2026-08']))
            ->assertViewHas('rows', fn($r) => $r->count() === 1 && $r->first()->date->toDateString() === '2026-08-11');
    }

    public function test_detail_scope_manager_sees_team_member_but_not_outsider(): void
    {
        $this->actingAs($this->p['manajer'])->get(route('attendance.recap.show', $this->p['aldora']))->assertOk();
        $this->get(route('attendance.recap.show', $this->p['hrd']))->assertForbidden();
        $this->get(route('attendance.recap.show', $this->p['owner']))->assertForbidden();

        $this->actingAs($this->p['hrd'])->get(route('attendance.recap.show', $this->p['aldora']))->assertOk();
    }

    // ---- E3 / E4: koreksi manual ---------------------------------------

    public function test_user_with_manage_access_can_correct_times_and_original_is_preserved(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');

        $this->actingAs($this->p['hrd'])->post(route('attendance.recap.correct', $row), [
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:00',
            'correction_note' => 'Mesin absen error',
        ])->assertSessionHas('status', 'Absensi berhasil dikoreksi.');

        $row->refresh();
        $this->assertSame('09:30', $row->clock_in_at->format('H:i'));
        $this->assertSame('18:00', $row->clock_out_at->format('H:i'));
        $this->assertSame('11:00', $row->original_clock_in_at->format('H:i'));
        $this->assertSame('15:00', $row->original_clock_out_at->format('H:i'));
        $this->assertSame($this->p['hrd']->id, $row->corrected_by);
        $this->assertSame('Mesin absen error', $row->correction_note);
        $this->assertTrue($row->wasCorrected());
    }

    public function test_second_correction_keeps_the_very_first_original(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');
        $hrd = $this->actingAs($this->p['hrd']);

        $hrd->post(route('attendance.recap.correct', $row), ['clock_in_time' => '10:00', 'correction_note' => 'koreksi 1']);
        $hrd->post(route('attendance.recap.correct', $row), ['clock_in_time' => '09:30', 'correction_note' => 'koreksi 2']);

        $row->refresh();
        $this->assertSame('09:30', $row->clock_in_at->format('H:i'));
        $this->assertSame('11:00', $row->original_clock_in_at->format('H:i'));
        $this->assertSame('koreksi 2', $row->correction_note);
    }

    public function test_correction_validation(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');
        $hrd = $this->actingAs($this->p['hrd']);

        $hrd->post(route('attendance.recap.correct', $row), ['clock_in_time' => '09:30'])
            ->assertSessionHasErrors(['correction_note' => 'Catatan alasan koreksi wajib diisi, biar transparan ke karyawan.']);

        $hrd->post(route('attendance.recap.correct', $row), ['correction_note' => 'x'])
            ->assertSessionHasErrors('clock_in_time');

        $hrd->post(route('attendance.recap.correct', $row), ['clock_in_time' => '9 pagi', 'correction_note' => 'x'])
            ->assertSessionHasErrors('clock_in_time');

        $this->assertFalse($row->fresh()->wasCorrected());
    }

    public function test_view_only_users_cannot_correct_even_by_posting_directly(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');

        // Kanaya: people = view, dan Aldora memang bawahannya.
        $this->actingAs($this->p['manajer'])->post(route('attendance.recap.correct', $row), [
            'clock_in_time' => '09:30',
            'correction_note' => 'coba-coba',
        ])->assertForbidden();

        $this->assertFalse($row->fresh()->wasCorrected());
    }

    public function test_correction_form_is_hidden_for_view_only_users(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');

        // Kanaya: people = view, Aldora bawahannya -> halaman terbuka, tapi tanpa form koreksi
        // (POST-nya memang 403, jadi tombolnya tidak boleh dipajang).
        $this->actingAs($this->p['manajer'])
            ->get(route('attendance.recap.show', $this->p['aldora']))
            ->assertOk()
            ->assertSee('18 Sep')
            // Bukan 'Koreksi jam absen': frasa itu juga ada di modal Panduan halaman.
            ->assertDontSee('Simpan Koreksi')
            ->assertDontSee('Alasan koreksi (wajib')
            ->assertDontSee(route('attendance.recap.correct', $row), false);
    }

    public function test_correction_form_is_shown_for_manage_users(): void
    {
        $row = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');

        $this->actingAs($this->p['hrd'])
            ->get(route('attendance.recap.show', $this->p['aldora']))
            ->assertOk()
            ->assertSee('Simpan Koreksi')
            ->assertSee(route('attendance.recap.correct', $row), false);

        // Manajer yang dinaikkan ke Manage juga melihatnya.
        $this->grant($this->p['manajer'], 'people', 'manage');

        $this->actingAs($this->p['manajer'])
            ->get(route('attendance.recap.show', $this->p['aldora']))
            ->assertOk()
            ->assertSee('Simpan Koreksi');
    }

    public function test_manager_with_manage_access_is_still_limited_to_her_team(): void
    {
        $this->grant($this->p['manajer'], 'people', 'manage');
        $outsider = $this->attendance($this->p['hrd'], '2026-09-18', '11:00', '15:00');
        $teammate = $this->attendance($this->p['aldora'], '2026-09-18', '11:00', '15:00');

        $manager = $this->actingAs($this->p['manajer']);
        $payload = ['clock_in_time' => '09:30', 'correction_note' => 'koreksi'];

        $manager->post(route('attendance.recap.correct', $outsider), $payload)->assertForbidden();
        $manager->post(route('attendance.recap.correct', $teammate), $payload)->assertSessionHas('status');
    }

    // ---- guest ----------------------------------------------------------

    public function test_recap_requires_login(): void
    {
        $this->get(route('attendance.recap.index'))->assertRedirect('/login');
        $this->get(route('attendance.recap.show', $this->p['aldora']))->assertRedirect('/login');
    }
}