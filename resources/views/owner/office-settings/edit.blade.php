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
                <label class="flex items-center gap-2.5 rounded-wsm border border-line bg-[#faf8f3] p-3.5">
                    <input type="hidden" name="auto_close_enabled" value="0">
                    <input type="checkbox" name="auto_close_enabled" value="1" @checked(old('auto_close_enabled', $setting->auto_close_enabled))
                        class="h-4 w-4">
                    <span class="text-xs">
                        <strong class="block">Tutup otomatis sesi yang lupa pulang</strong>
                        <span class="text-muted">Kalau aktif dan karyawan lupa checkout & gak punya Lembur
                            disetujui, sistem nutup sesi kerja tanggal itu di jam selesai di atas (bukan real-time —
                            kepicu pas ada aktivitas absen/riwayat berikutnya, hosting shared gak punya cron
                            custom). Kalau dimatikan, sesi itu tetap terbuka sebagai "Lupa Absen Pulang" dan
                            karyawan mengajukan koreksi presensi.</span>
                    </span>
                </label>
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
                        ke window normal) masuk status "Kurang Jam Kerja" & diakumulasi per blok (ukurannya diatur di
                        bawah) di rekap bulanan.</p>
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Ukuran Satu Blok Kurang Jam
                        (menit)</label>
                    <input type="number" name="shortage_block_minutes"
                        value="{{ old('shortage_block_minutes', $setting->shortage_block_minutes) }}" min="15"
                        max="240" class="input-wsm" required>
                    <p class="mt-1 text-[11px] text-muted">Kekurangan jam kerja dalam sebulan dijumlahkan lalu
                        dipotong per blok ini. Sisa di bawah satu blok tidak dipotong bulan itu. Contoh: blok 60
                        menit dan kurang total 150 menit = 2 blok dipotong, sisa 30 menit.</p>
                    @error('shortage_block_minutes')
                        <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Pembagi Hari Kerja Payroll (hari /
                        bulan)</label>
                    <input type="number" name="payroll_work_days_divisor"
                        value="{{ old('payroll_work_days_divisor', $setting->payrollWorkDaysDivisor()) }}" min="1"
                        max="31" class="input-wsm" required>
                    <p class="mt-1 text-[11px] text-muted">Tarif harian = gaji pokok ÷ pembagi ini (bawaan 22).
                        Potongan hari absen = 1 tarif harian per hari yang ditandai Absen. Potongan kurang jam =
                        tarif harian ÷ jam kerja per hari × jam yang terpotong (per blok di atas). Tidak ada tarif
                        rupiah yang perlu diisi.</p>
                    @error('payroll_work_days_divisor')
                        <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Fase 16 — warna aksen sidebar, sebelumnya cuma kolom DB
             nganggur dari Fase 7 (padanan pengaturan warna CEO
             Dashboard/Work Control di prototype v18). "CEO" nge-tint
             active pill link Owner-only (Dashboard/Karyawan/Struktur
             Organisasi/Pengaturan Kantor), "Work" nge-tint tab aktif
             di Work Control (MoM & Memo/Work Tracker/Rapat & Action
             Item) — lihat layouts/app.blade.php & tab bar
             dashboard/work/*. Modul lain (KPI, Payroll, dst) TETAP
             hitam standar, gak ikut warna ini — sengaja cuma 2 area
             itu yang dulu punya accent terpisah di prototype. --}}
        <div>
            <h3 class="mb-3 text-sm font-extrabold">Warna Aksen Sidebar</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Aksen Dashboard (Owner)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="ceo_accent_color"
                            value="{{ old('ceo_accent_color', $setting->ceo_accent_color) }}"
                            class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1">
                        <span class="text-xs text-muted">{{ old('ceo_accent_color', $setting->ceo_accent_color) }}</span>
                    </div>
                    @error('ceo_accent_color')
                        <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-bold text-muted">Aksen Work Control</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="work_accent_color"
                            value="{{ old('work_accent_color', $setting->work_accent_color) }}"
                            class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1">
                        <span
                            class="text-xs text-muted">{{ old('work_accent_color', $setting->work_accent_color) }}</span>
                    </div>
                    @error('work_accent_color')
                        <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <button type="submit" class="btn-wsm-black justify-self-start">Simpan Pengaturan</button>
    </form>

    {{-- 2026-10-02 — Beranda Publik: padanan "Landing Copy & Banner" di Settings
        prototype. Form terpisah (bukan bagian form di atas) karena disimpan ke
        route sendiri dan punya tombol "Kembalikan bawaan". --}}
    @php
        $landing = $setting->landing();
        $cardsOld = old('cards', $landing['cards']);
    @endphp
    <section class="mt-8" x-data="{
        headline: @js(old('headline', $landing['headline'])),
        tagline: @js(old('tagline', $landing['tagline'])),
        cards: @js(array_values($cardsOld)),
        textOn(hex) {
            const h = (hex || '').replace('#', '');
            if (h.length !== 6) return '#ffffff';
            const r = parseInt(h.slice(0, 2), 16),
                g = parseInt(h.slice(2, 4), 16),
                b = parseInt(h.slice(4, 6), 16);
            return ((r * 299 + g * 587 + b * 114) / 1000) >= 150 ? '#13220d' : '#ffffff';
        },
    }">
        <div class="mb-4">
            <h3 class="text-[26px] font-black leading-none tracking-tight">Beranda Publik</h3>
            <p class="mt-1 text-[13px] text-muted">Judul, tagline, dan 4 banner di bagian atas halaman depan website
                (yang dibuka sebelum login). Perubahan langsung tampil di
                <a href="{{ route('public.home') }}" target="_blank" rel="noopener"
                    class="font-bold underline">beranda</a>.
            </p>
        </div>

        <form method="POST" action="{{ route('owner.landing.update') }}" class="card-wsm-white grid gap-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="mb-1 block text-[11px] font-bold text-muted">Judul Besar</label>
                <input type="text" name="headline" x-model="headline" maxlength="120" class="input-wsm" required>
                @error('headline')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="mb-1 block text-[11px] font-bold text-muted">Tagline</label>
                <textarea name="tagline" x-model="tagline" rows="3" maxlength="300" class="input-wsm" required></textarea>
                @error('tagline')
                    <p class="mt-1 text-xs font-semibold text-[#a83d35]">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <template x-for="(card, i) in cards" :key="i">
                    <div class="rounded-wsm border border-line bg-[#faf8f3] p-3.5">
                        <p class="mb-2 text-[11px] font-extrabold uppercase text-muted" x-text="'Banner ' + (i + 1)"></p>
                        <div class="grid gap-2">
                            <input type="text" :name="`cards[${i}][label]`" x-model="card.label" maxlength="30"
                                placeholder="Label kecil" class="input-wsm" required>
                            <input type="text" :name="`cards[${i}][title]`" x-model="card.title" maxlength="30"
                                placeholder="Judul banner" class="input-wsm" required>
                            <div class="flex items-center gap-2">
                                <input type="color" :name="`cards[${i}][color]`" x-model="card.color"
                                    class="h-10 w-14 cursor-pointer rounded-lg border border-line bg-white p-1">
                                <span class="text-xs text-muted" x-text="card.color"></span>
                            </div>
                        </div>
                        <div class="mt-3 flex min-h-20 flex-col justify-between rounded-2xl p-3"
                            :style="`background-color:${card.color};color:${textOn(card.color)}`">
                            <span class="text-[10px] font-black uppercase" x-text="card.label"></span>
                            <strong class="text-xl font-black" x-text="card.title"></strong>
                        </div>
                    </div>
                </template>
            </div>
            @if ($errors->has('cards') || $errors->has('cards.*'))
                <p class="text-xs font-semibold text-[#a83d35]">Cek lagi isi banner: label dan judul wajib diisi, warna
                    harus
                    valid.</p>
            @endif

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-wsm-black">Simpan Beranda</button>
                @if ($landing['customized'])
                    <button type="submit" form="landing-reset-form" class="btn-wsm-white">Kembalikan Bawaan</button>
                @endif
            </div>
        </form>
        @if ($landing['customized'])
            <form id="landing-reset-form" method="POST" action="{{ route('owner.landing.reset') }}" class="hidden"
                data-confirm="Judul, tagline, dan banner dikembalikan ke teks bawaan."
                data-confirm-title="Kembalikan bawaan?" data-confirm-button="Ya, kembalikan">
                @csrf
                @method('DELETE')
            </form>
        @endif
    </section>
@endsection
