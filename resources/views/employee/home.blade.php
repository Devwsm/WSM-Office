{{--
    employee/home.blade.php
    ---------------------------------------------------------------------
    Fase 4 — kartu absen fungsional: mode Kantor/WFH, "Test Lokasi"
    (map + radius, biar karyawan sadar sebelum absen jauh dari kantor),
    foto selfie opsional (kamera langsung, bukan galeri), dan modal
    konfirmasi map + jarak sebelum absen beneran tersimpan.
    Logic ada di resources/js/attendance.js (Alpine component
    `attendanceWidget`).
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Home', 'navActive' => 'home'])

@section('content')
    <div class="employee-hero mb-7">
        <h1 class="text-[44px] font-black leading-[0.98] tracking-tight">Halo, {{ explode(' ', auth()->user()->name)[0] }} 👋
        </h1>
        <p class="mt-1 text-[15px] text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
    </div>

    @if ($forgottenAttendance)
        <div class="mb-3.5 rounded-wsm-lg border border-[#f1c7c2] bg-[#fff0ee] p-4 text-[#a83d35]">
            <p class="text-xs font-black">⚠ Kamu belum absen pulang tanggal
                {{ $forgottenAttendance->date->translatedFormat('d F Y') }}</p>
            <p class="mt-1 text-[11px]">Datanya tetap tersimpan (jam masuk
                {{ $forgottenAttendance->clock_in_at->format('H:i') }}), tapi jam pulangnya kosong. Koreksi manual
                belum ada di sistem — sampaikan langsung ke Manajer/Owner ya.</p>
        </div>
    @endif

    <div x-data="attendanceWidget({
        defaultMode: {{ \Illuminate\Support\Js::from($attendance->mode ?? 'kantor') }},
        officeLat: {{ \Illuminate\Support\Js::from($officeSetting->latitude) }},
        officeLng: {{ \Illuminate\Support\Js::from($officeSetting->longitude) }},
        officeName: {{ \Illuminate\Support\Js::from($officeSetting->office_name) }},
        radiusMeters: {{ \Illuminate\Support\Js::from($officeSetting->radius_meters) }},
        hasClockIn: {{ \Illuminate\Support\Js::from((bool) $attendance?->clock_in_at) }},
        hasClockOut: {{ \Illuminate\Support\Js::from((bool) $attendance?->clock_out_at) }},
    })" id="attendance-card" class="mb-3.5 scroll-mt-6">

        {{-- Form tersembunyi — hidden input diisi dari JS pas user konfirmasi di modal --}}
        <form x-ref="clockInForm" method="POST" action="{{ route('employee.attendance.clockIn') }}" class="hidden">
            @csrf
            <input type="hidden" name="mode">
            <input type="hidden" name="work_context">
            <input type="hidden" name="lat">
            <input type="hidden" name="lng">
            <input type="hidden" name="accuracy">
            <input type="hidden" name="photo">
        </form>
        <form x-ref="clockOutForm" method="POST" action="{{ route('employee.attendance.clockOut') }}" class="hidden">
            @csrf
            <input type="hidden" name="lat">
            <input type="hidden" name="lng">
            <input type="hidden" name="accuracy">
            <input type="hidden" name="photo">
        </form>

        @if ($attendance?->clock_out_at)
            {{-- Selesai --}}
            <div class="rounded-wsm-lg bg-[#d7d1c8] p-6 text-[#5e5952]">
                <span class="text-[11px] font-black uppercase tracking-wide text-[#5e5952]/75">Status Kehadiran</span>
                <p class="mt-3 text-2xl font-black">Absensi Hari Ini Selesai</p>
                <p class="mt-1 text-xs">
                    Masuk {{ $attendance->clock_in_at->format('H:i') }} · Pulang
                    {{ $attendance->clock_out_at->format('H:i') }}
                </p>
                <div class="mt-4">
                    <span class="{{ $attendance->statusBadgeClass($officeSetting) }}">
                        {{ $attendance->statusLabel($officeSetting) }}
                    </span>
                </div>
            </div>
        @else
            <div class="rounded-wsm-lg p-6 text-white" :class="hasClockIn ? 'bg-brand-green' : 'bg-brand-blue'">
                <span class="text-[11px] font-black uppercase tracking-wide text-white/75">Status Kehadiran</span>
                <p class="mt-3 text-2xl font-black" x-text="hasClockIn ? 'Sedang Bekerja' : 'Belum Absen'"></p>
                @if ($attendance?->clock_in_at)
                    <p class="mt-1 text-xs text-white/70">Check In {{ $attendance->clock_in_at->format('H:i') }}
                    </p>
                @endif

                {{-- Pilihan mode, cuma sebelum absen masuk --}}
                <div class="mode-toggle-wsm mt-5" x-show="!hasClockIn">
                    <button type="button" @click="mode = 'kantor'" class="mode-toggle-wsm-btn"
                        :class="mode === 'kantor' ? 'active' : ''">
                        🏢 Kantor
                    </button>
                    <button type="button" @click="mode = 'wfh'" class="mode-toggle-wsm-btn"
                        :class="mode === 'wfh' ? 'active' : ''">
                        🏠 WFH
                    </button>
                </div>
                <input x-show="!hasClockIn" x-model="workContext" type="text"
                    placeholder="Catatan (opsional) — mis. nama project/lokasi" class="input-wsm mt-2.5!"
                    style="background:rgba(255,255,255,.92)">

                {{-- Status geo + tombol test lokasi --}}
                <div class="geo-status-wsm mt-3.5" style="background:rgba(255,255,255,.95)">
                    <div class="min-w-0">
                        <strong class="block text-xs text-ink">📍 Geo Tag</strong>
                        <span class="mt-0.5 block text-[11px] text-muted" x-show="!geo">
                            Lokasi direkam otomatis saat kamu absen.
                        </span>
                        <span class="mt-0.5 block text-[11px] text-muted" x-show="geo && mode === 'kantor'"
                            x-text="distance !== null ? distance + 'm dari kantor' + (withinRadius ? ' — dalam radius' : ' — DI LUAR RADIUS') : ''">
                        </span>
                        <span class="mt-0.5 block text-[11px] text-muted" x-show="geo && mode === 'wfh'">
                            Lokasi kebaca (radius tidak dicek untuk mode WFH).
                        </span>
                    </div>
                    <button type="button" @click="testLocation()" :disabled="geoLoading"
                        class="btn-wsm-white flex-none py-2! text-[11px]!">
                        <span x-show="!geoLoading">Test Lokasi</span>
                        <span x-show="geoLoading">Mencari...</span>
                    </button>
                </div>

                {{-- Foto selfie opsional --}}
                <div class="mt-3.5 rounded-2xl border border-line bg-white p-3.5" style="background:rgba(255,255,255,.95)">
                    <div class="flex items-center gap-3">
                        <div
                            class="grid h-14 w-14 flex-none place-items-center overflow-hidden rounded-2xl bg-[#ece7df] text-[10px] font-black text-muted">
                            <template x-if="photoDataUrl">
                                <img :src="photoDataUrl" class="h-full w-full object-cover" alt="Preview selfie">
                            </template>
                            <template x-if="!photoDataUrl">
                                <span>📷</span>
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <strong class="block text-xs text-ink">Foto Selfie (opsional)</strong>
                            <span class="mt-0.5 block text-[11px] text-muted">Langsung dari kamera, bukan dari
                                galeri.</span>
                        </div>
                        <label class="btn-wsm-white flex-none py-2! text-[11px]!"
                            x-text="photoDataUrl ? 'Ganti' : 'Ambil Foto'">
                            <input x-ref="photoInput" @change="handlePhotoInput($event)" type="file" accept="image/*"
                                capture="user" class="hidden">
                        </label>
                    </div>
                    <button type="button" x-show="photoDataUrl" @click="removePhoto()"
                        class="mt-2 text-[11px] font-extrabold text-[#a83d35]">
                        Hapus foto
                    </button>
                </div>

                {{-- Tombol utama --}}
                <button type="button" x-show="!hasClockIn" @click="openConfirm('clockIn')" :disabled="geoLoading"
                    class="mt-4 w-full rounded-wsm bg-black/85 py-4 text-sm font-extrabold text-white transition hover:bg-black">
                    Absen Masuk
                </button>
                <button type="button" x-show="hasClockIn" @click="openConfirm('clockOut')" :disabled="geoLoading"
                    class="mt-4 w-full rounded-wsm bg-black/15 py-4 text-sm font-extrabold text-white transition hover:bg-black/25">
                    Absen Pulang
                </button>
            </div>
        @endif

        {{-- Modal konfirmasi: map + jarak + tombol konfirmasi --}}
        <div x-show="showModal" x-cloak
            class="fixed inset-0 z-50 grid place-items-end bg-black/40 p-0 sm:place-items-center sm:p-4"
            style="display:none">
            <div @click.outside="closeModal()"
                class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-4xl bg-cream p-5 sm:rounded-4xl">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-lg font-black"
                        x-text="modalPurpose === 'test' ? 'Test Lokasi' : (modalPurpose === 'clockIn' ? 'Konfirmasi Absen Masuk' : 'Konfirmasi Absen Pulang')">
                    </h3>
                    <button type="button" @click="closeModal()"
                        class="grid h-9 w-9 place-items-center rounded-2xl bg-[#ece7dd]">✕</button>
                </div>

                <div class="wsm-map" x-ref="mapEl"></div>

                <div class="mt-3.5 rounded-2xl border border-line bg-white p-3.5">
                    <template x-if="mode === 'kantor'">
                        <p class="text-xs" :class="withinRadius === false ? 'text-[#a83d35] font-extrabold' : 'text-ink'">
                            <span x-text="distance"></span>m dari <span x-text="officeName"></span> —
                            <span
                                x-text="withinRadius ? 'dalam radius ' + radiusMeters + 'm' : 'DI LUAR radius ' + radiusMeters + 'm'"></span>
                        </p>
                    </template>
                    <template x-if="mode === 'wfh'">
                        <p class="text-xs text-ink">Mode WFH — radius kantor tidak dicek, lokasi tetap dicatat.</p>
                    </template>
                    <p class="mt-1 text-[11px] text-muted" x-show="withinRadius === false && mode === 'kantor'">
                        Tetap boleh absen, tapi bakal tercatat "di luar radius" di rekap Manajer/Owner.
                    </p>
                </div>

                <template x-if="modalPurpose !== 'test'">
                    <div class="mt-4 flex gap-2.5">
                        <button type="button" @click="closeModal()" class="btn-wsm-white flex-1">Batal</button>
                        <button type="button" @click="confirmSubmit()" :disabled="submitting"
                            class="btn-wsm-black flex-1">
                            <span x-show="!submitting">Ya, Absen Sekarang</span>
                            <span x-show="submitting">Menyimpan...</span>
                        </button>
                    </div>
                </template>
                <template x-if="modalPurpose === 'test'">
                    <button type="button" @click="closeModal()" class="btn-wsm-black mt-4 w-full">Tutup</button>
                </template>
            </div>
        </div>
    </div>

    <div class="card-wsm-white">
        <p class="mb-1.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Info dari Owner</p>
        <p class="text-xs text-muted">Belum ada memo — fitur aktif di Fase 6.</p>
    </div>
@endsection
