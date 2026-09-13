<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateOfficeSettingRequest;
use App\Models\AuditLog;
use App\Models\OfficeSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * OfficeSettingController (Owner)
 * ---------------------------------------------------------------------
 * Fase 7 — sebelumnya `office_settings` cuma bisa diubah lewat
 * `OfficeSettingSeeder` (developer yang ubah, deploy ulang). Sekarang
 * Owner bisa ubah sendiri: nama/alamat/koordinat kantor, radius,
 * toggle geo & enforce-radius, jam kerja normal (window WFO 09:30-20:00
 * default). Singleton — selalu edit baris id=1, gak ada create/delete.
 *
 * Fase 15 (instrumentasi, 2026-09-13) — `update()` dicatat ke
 * AuditLog::record() — pengaturan kantor jadi acuan absensi/lembur/
 * payroll seluruh sistem, sensitif kalau berubah tanpa jejak.
 * ---------------------------------------------------------------------
 */
class OfficeSettingController extends Controller
{
    public function edit()
    {
        return view('owner.office-settings.edit', [
            'setting' => OfficeSetting::current(),
        ]);
    }

    public function update(UpdateOfficeSettingRequest $request)
    {
        $data = $request->validated();
        $data['work_start_time'] .= ':00';
        $data['normal_end_time'] .= ':00';

        $setting = OfficeSetting::query()->firstOrNew(['id' => 1]);
        $setting->fill($data);
        $setting->save();

        /** @var User $actor */
        $actor = Auth::user();
        AuditLog::record('Pengaturan kantor diubah', "Pengaturan kantor diubah oleh {$actor->name}.", $actor);

        return back()->with('status', 'Pengaturan kantor berhasil disimpan.');
    }
}