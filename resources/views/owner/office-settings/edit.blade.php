{{--
    owner/office-settings/edit.blade.php
    ---------------------------------------------------------------------
    Fase 7 — halaman BARU, sebelumnya office_settings cuma bisa diubah
    lewat OfficeSettingSeeder (developer). Singleton, jadi cuma ada 1
    form "edit", gak ada index/create/delete.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Pengaturan Kantor', 'navActive' => 'office-settings'])

@section('content')
    <div class="mb-5">
        <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">Pengaturan Kantor</h2>
        <p class="mt-1 text-[13px] text-muted">Lokasi & radius absen, serta jam kerja normal WFO. Perubahan di sini
            langsung berlaku buat absen berikutnya.</p>
    </div>

    <form method="POST" action="{{ route('owner.office-settings.update') }}" class="grid gap-5">
        @csrf
        @method('PATCH')

        <div class="card-wsm-white">
            <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Lokasi & Radius</p>
            <div class="grid gap-3">
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Nama Kantor</label>
                    <input type="text" name="office_name" value="{{ old('office_name', $setting->office_name) }}"
                        class="input-wsm" required>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Alamat</label>
                    <textarea name="address" rows="2" class="input-wsm" required>{{ old('address', $setting->address) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Latitude</label>
                        <input type="text" name="latitude" value="{{ old('latitude', $setting->latitude) }}"
                            class="input-wsm" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Longitude</label>
                        <input type="text" name="longitude" value="{{ old('longitude', $setting->longitude) }}"
                            class="input-wsm" required>
                    </div>
                </div>
                <p class="text-[11px] text-muted">Cara cepat cari koordinat: buka lokasi kantor di Google Maps, klik
                    kanan titiknya, koordinat langsung ke-copy.</p>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Radius Toleransi (meter)</label>
                    <input type="number" name="radius_meters" value="{{ old('radius_meters', $setting->radius_meters) }}"
                        min="10" max="5000" class="input-wsm" required>
                </div>

                <label class="flex items-center gap-2.5 rounded-wsm border border-line bg-[#faf8f3] p-3.5">
                    <input type="checkbox" name="geo_attendance_enabled" value="1" @checked(old('geo_attendance_enabled', $setting->geo_attendance_enabled))
                        class="h-4 w-4">
                    <span class="text-xs">
                        <strong class="block">Aktifkan pengecekan geo saat absen</strong>
                        <span class="text-muted">Kalau dimatikan, absen mode Kantor gak ngitung jarak sama sekali
                            (dianggap kayak WFH).</span>
                    </span>
                </label>
                <label class="flex items-center gap-2.5 rounded-wsm border border-line bg-[#faf8f3] p-3.5">
                    <input type="checkbox" name="enforce_radius" value="1" @checked(old('enforce_radius', $setting->enforce_radius))
                        class="h-4 w-4">
                    <span class="text-xs">
                        <strong class="block">Tandai "di luar radius" kalau kelewat batas</strong>
                        <span class="text-muted">Jarak tetap dicatat walau dimatikan (buat informasi), tapi gak
                            pernah dianggap masalah. Absen TIDAK PERNAH diblokir gara-gara radius, di kedua kondisi
                            ini.</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="card-wsm-white">
            <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Jam Kerja Normal (WFO)</p>
            <div class="grid gap-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Jam Mulai</label>
                        <input type="time" name="work_start_time"
                            value="{{ old('work_start_time', substr($setting->work_start_time, 0, 5)) }}" class="input-wsm"
                            required>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-bold text-muted">Jam Selesai (auto-close)</label>
                        <input type="time" name="normal_end_time"
                            value="{{ old('normal_end_time', substr($setting->normal_end_time, 0, 5)) }}" class="input-wsm"
                            required>
                    </div>
                </div>
                <p class="text-[11px] text-muted">Kalau karyawan lupa checkout & gak punya Lembur disetujui, sistem
                    otomatis nutup sesi kerja tanggal itu di jam selesai ini (bukan real-time — kepicu pas ada
                    aktivitas absen/riwayat berikutnya, hosting shared gak punya cron custom).</p>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Toleransi Telat (menit)</label>
                    <input type="number" name="late_tolerance_minutes"
                        value="{{ old('late_tolerance_minutes', $setting->late_tolerance_minutes) }}" min="0"
                        max="120" class="input-wsm" required>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Minimal Menit Kerja/Hari</label>
                    <input type="number" name="required_work_minutes"
                        value="{{ old('required_work_minutes', $setting->required_work_minutes) }}" min="60"
                        max="960" class="input-wsm" required>
                    <p class="mt-1 text-[11px] text-muted">Kurang dari ini (dihitung dari jam kerja yang udah diklem
                        ke window normal) masuk status "Kurang Jam Kerja" & diakumulasi per blok 60 menit di rekap
                        bulanan.</p>
                </div>
            </div>
        </div>

        <button type="submit" class="btn-wsm-black justify-self-start">Simpan Pengaturan</button>
    </form>
@endsection
