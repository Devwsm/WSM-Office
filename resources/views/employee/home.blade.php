{{--
    employee/home.blade.php
    ---------------------------------------------------------------------
    Fase 4 — kartu absen fungsional: mode Kantor/WFH, "Test Lokasi"
    (map + radius, biar karyawan sadar sebelum absen jauh dari kantor),
    foto selfie opsional (kamera langsung, bukan galeri), dan modal
    konfirmasi map + jarak sebelum absen beneran tersimpan.
    Logic ada di resources/js/attendance.js (Alpine component
    `attendanceWidget`).

    Fase 7 nambah mode Lapangan/Gigs (boleh multi-sesi per hari — lihat
    $sessions/$canStartNewSession dari HomeController). `$attendance`
    yang dioper ke sini sekarang representasi "sesi yang relevan buat
    ditampilin" (sesi terbuka kalau ada, kalau enggak sesi terakhir hari
    ini), BUKAN lagi asumsi "1 baris = 1 hari".
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Home', 'navActive' => 'home'])

@section('content')
    <div class="employee-hero mb-7">
        <h1 class="text-[44px] font-black leading-[0.98] tracking-tight">Halo, {{ explode(' ', auth()->user()->name)[0] }} 👋
        </h1>
        <p class="mt-1 text-[15px] text-muted">{{ now()->translatedFormat('l, d F Y') }}</p>
        {{-- Fase 8: job title + divisi di bawah tanggal, cuma muncul kalau
             dua-duanya keisi (kolomnya udah ada dari Fase 2, sebelumnya
             cuma dipakai di halaman Karyawan Owner & kartu Profile). --}}
        @if (auth()->user()->job_title || auth()->user()->division)
            <p class="mt-1 text-[12px] font-semibold text-[#8c8578]">
                {{ auth()->user()->job_title }}{{ auth()->user()->job_title && auth()->user()->division ? ' · ' : '' }}{{ auth()->user()->division }}
            </p>
        @endif
    </div>

    {{-- Fase 8: banner cuti tim bulan ini — cuti_tahunan & izin_pribadi
         doang (izin_sakit privat, sengaja gak diumumin). --}}
    @if ($teamLeavesThisMonth->isNotEmpty())
        <div class="mb-3.5 rounded-wsm-lg border border-line bg-white p-4">
            <p class="text-[11px] font-extrabold uppercase tracking-wide text-[#5e5952]">Cuti Tim Bulan Ini</p>
            <div class="mt-2 grid gap-1.5">
                @foreach ($teamLeavesThisMonth as $leave)
                    <p class="text-xs text-muted">
                        <strong class="text-ink">{{ $leave->user->name }}</strong> — {{ $leave->typeLabel() }},
                        {{ $leave->start_date->translatedFormat('d M') }}
                        @if (!$leave->start_date->isSameDay($leave->end_date))
                            – {{ $leave->end_date->translatedFormat('d M') }}
                        @endif
                    </p>
                @endforeach
            </div>
        </div>
    @endif

    @if ($forgottenAttendance)
        <div class="mb-3.5 rounded-wsm-lg border border-[#f1c7c2] bg-[#fff0ee] p-4 text-[#a83d35]">
            <p class="text-xs font-black">⚠
                {{ $forgottenAttendance->auto_closed ? 'Absen pulang tanggal' : 'Kamu belum absen pulang tanggal' }}
                {{ $forgottenAttendance->date->translatedFormat('d F Y') }}
                {{ $forgottenAttendance->auto_closed ? 'ditutup otomatis sistem' : '' }}</p>
            <p class="mt-1 text-[11px]">Datanya tetap tersimpan (jam masuk
                {{ $forgottenAttendance->clock_in_at->format('H:i') }}){{ $forgottenAttendance->auto_closed ? ', jam pulang dicatat otomatis karena lupa checkout' : ', tapi jam pulangnya kosong' }}.
                Ada yang keliru? Sampaikan langsung ke Manajer/Owner buat dikoreksi.</p>
        </div>
    @endif

    @if ($todayLeave)
        <div class="rounded-wsm-lg bg-brand-blue p-6 text-white mb-3.5">
            <span class="text-[11px] font-black uppercase tracking-wide text-white/75">Status Kehadiran</span>
            <p class="mt-3 text-2xl font-black">Kamu sedang {{ $todayLeave->typeLabel() }} hari ini</p>
            <p class="mt-1 text-xs text-white/70">
                {{ $todayLeave->start_date->translatedFormat('d M Y') }}
                @if (!$todayLeave->start_date->isSameDay($todayLeave->end_date))
                    – {{ $todayLeave->end_date->translatedFormat('d M Y') }}
                @endif
                · disetujui oleh {{ $todayLeave->approver?->name ?? '-' }}
            </p>
            <p class="mt-3 text-[11px] text-white/70">Nggak perlu absen selama masa izin/cuti ini. Cek detail di menu
                Request.</p>
        </div>
    @else
        {{-- Fase 7: ringkasan sesi hari ini, cuma muncul kalau udah ada >1 sesi (mode Lapangan/Gigs) --}}
        @if ($sessions->count() > 1)
            <div class="card-wsm-white mb-3.5">
                <p class="mb-2 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Sesi Hari Ini</p>
                <div class="grid gap-1.5">
                    @foreach ($sessions as $session)
                        <div class="flex items-center justify-between text-xs">
                            <span>Sesi {{ $session->session_number }} ·
                                {{ match ($session->mode) {
                                    'wfh' => 'WFH',
                                    'lapangan' => 'Lapangan',
                                    'gigs' => 'Gigs',
                                    default => 'Kantor',
                                } }}</span>
                            <span class="text-muted">
                                {{ $session->clock_in_at?->format('H:i') ?? '--:--' }} –
                                {{ $session->clock_out_at?->format('H:i') ?? 'berjalan' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div x-data="attendanceWidget({
            defaultMode: {{ \Illuminate\Support\Js::from($attendance->mode ?? 'kantor') }},
            officeLat: {{ \Illuminate\Support\Js::from($officeSetting->latitude) }},
            officeLng: {{ \Illuminate\Support\Js::from($officeSetting->longitude) }},
            officeName: {{ \Illuminate\Support\Js::from($officeSetting->office_name) }},
            radiusMeters: {{ \Illuminate\Support\Js::from($officeSetting->radius_meters) }},
            hasClockIn: {{ \Illuminate\Support\Js::from((bool) ($attendance?->clock_in_at && !$attendance?->clock_out_at)) }},
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

            @if ($attendance?->clock_out_at && !$canStartNewSession)
                {{-- Selesai (Kantor/WFH, gak bisa sesi baru) --}}
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
                    <p class="mt-3 text-2xl font-black"
                        x-text="hasClockIn ? 'Sedang Bekerja' : (({{ \Illuminate\Support\Js::from($canStartNewSession && $sessions->isNotEmpty()) }}) ? 'Mau Absen Sesi Baru?' : 'Belum Absen')">
                    </p>
                    @if ($attendance?->clock_in_at && !$attendance?->clock_out_at)
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
                        <button type="button" @click="mode = 'lapangan'" class="mode-toggle-wsm-btn"
                            :class="mode === 'lapangan' ? 'active' : ''">
                            🚗 Lapangan
                        </button>
                        <button type="button" @click="mode = 'gigs'" class="mode-toggle-wsm-btn"
                            :class="mode === 'gigs' ? 'active' : ''">
                            🎤 Gigs
                        </button>
                    </div>
                    <p class="mt-1.5 text-[11px] text-white/70"
                        x-show="!hasClockIn && (mode === 'lapangan' || mode === 'gigs')">
                        Mode ini boleh absen masuk-pulang berkali-kali dalam sehari (per kunjungan/acara).
                    </p>
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
                            <span class="mt-0.5 block text-[11px] text-muted" x-show="geo && mode !== 'kantor'">
                                Lokasi kebaca (radius kantor tidak dicek untuk mode ini).
                            </span>
                        </div>
                        <button type="button" @click="testLocation()" :disabled="geoLoading"
                            class="btn-wsm-white flex-none py-2! text-[11px]!">
                            <span x-show="!geoLoading">Test Lokasi</span>
                            <span x-show="geoLoading">Mencari...</span>
                        </button>
                    </div>

                    {{-- Foto selfie opsional --}}
                    <div class="mt-3.5 rounded-2xl border border-line bg-white p-3.5"
                        style="background:rgba(255,255,255,.95)">
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
                                <input x-ref="photoInput" @change="handlePhotoInput($event)" type="file"
                                    accept="image/*" capture="user" class="hidden">
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
                            <p class="text-xs"
                                :class="withinRadius === false ? 'text-[#a83d35] font-extrabold' : 'text-ink'">
                                <span x-text="distance"></span>m dari <span x-text="officeName"></span> —
                                <span
                                    x-text="withinRadius ? 'dalam radius ' + radiusMeters + 'm' : 'DI LUAR radius ' + radiusMeters + 'm'"></span>
                            </p>
                        </template>
                        <template x-if="mode !== 'kantor'">
                            <p class="text-xs text-ink">Radius kantor tidak dicek untuk mode ini, lokasi tetap
                                dicatat.</p>
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
    @endif

    @php
        $me = auth()->user();
        $visibleMemos = $memos->reject(fn($m) => $m->isHiddenBy($me))->take(4);
        $hiddenMemoCount = $memos->filter(fn($m) => $m->isHiddenBy($me))->count();
    @endphp
    <div x-data="{ showHidden: false }" class="card-wsm-white">
        <div class="mb-1.5 flex items-center justify-between gap-3">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Info dari Owner</p>
            @if ($hiddenMemoCount > 0)
                <button type="button" @click="showHidden = !showHidden"
                    class="text-[10px] font-extrabold text-muted underline decoration-dotted"
                    x-text="showHidden ? 'Sembunyikan lagi' : '{{ $hiddenMemoCount }} disembunyikan'"></button>
            @endif
        </div>

        @if ($memos->isEmpty())
            <p class="text-xs text-muted">Belum ada memo.</p>
        @else
            <div class="grid gap-3">
                @foreach ($visibleMemos as $memo)
                    <div class="{{ !$loop->last ? 'border-b border-[#eee8df] pb-3' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    @if ($memo->pinned)
                                        <span class="text-[10px] font-extrabold text-[#a8873d]">📌</span>
                                    @endif
                                    <strong class="text-xs">{{ $memo->title }}</strong>
                                </div>
                                <span class="text-[10px] text-muted">{{ $memo->creator->name }} ·
                                    {{ $memo->created_at->translatedFormat('d M Y') }}</span>
                            </div>
                            <span
                                class="flex-none rounded-full px-2 py-0.5 text-[9px] font-extrabold {{ $memo->isReadBy($me) ? 'bg-[#eee8df] text-muted' : 'bg-brand-blue text-white' }}">
                                {{ $memo->isReadBy($me) ? 'READ' : 'UNREAD' }}
                            </span>
                        </div>
                        <p class="mt-1.5 whitespace-pre-line text-xs text-muted">{{ $memo->content }}</p>

                        <div class="mt-2 flex gap-3.5">
                            <form method="POST" action="{{ route('employee.memo.toggleRead', $memo) }}">
                                @csrf
                                <button type="submit"
                                    class="text-[10px] font-extrabold text-[#5e5951] underline decoration-dotted">
                                    {{ $memo->isReadBy($me) ? 'Tandai Belum Dibaca' : 'Tandai Sudah Dibaca' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('employee.memo.toggleHidden', $memo) }}">
                                @csrf
                                <button type="submit"
                                    class="text-[10px] font-extrabold text-[#5e5951] underline decoration-dotted">
                                    Sembunyikan
                                </button>
                            </form>
                        </div>

                        @include('memo._thread', [
                            'memo' => $memo,
                            'replyRoute' => route('employee.memo.reply', $memo),
                        ])
                    </div>
                @endforeach
            </div>

            {{-- Memo yang disembunyikan — sengaja tetap di-render di DOM
                 (bukan lewat request baru), toggle-nya murni CSS/Alpine.
                 Wajar buat 3-4 item/bulan kayak konteks perusahaan ini,
                 lihat README kalau volume memo-nya jauh lebih besar
                 nanti (baru perlu pindah ke query terpisah). --}}
            @if ($hiddenMemoCount > 0)
                <div x-show="showHidden" x-cloak class="mt-3 grid gap-3 border-t border-[#eee8df] pt-3">
                    @foreach ($memos->filter(fn($m) => $m->isHiddenBy($me)) as $memo)
                        <div class="opacity-60">
                            <div class="flex items-center gap-1.5">
                                <strong class="text-xs">{{ $memo->title }}</strong>
                            </div>
                            <span class="text-[10px] text-muted">{{ $memo->creator->name }} ·
                                {{ $memo->created_at->translatedFormat('d M Y') }}</span>
                            <p class="mt-1 line-clamp-2 text-xs text-muted">{{ $memo->content }}</p>
                            <form method="POST" action="{{ route('employee.memo.toggleHidden', $memo) }}"
                                class="mt-1.5">
                                @csrf
                                <button type="submit"
                                    class="text-[10px] font-extrabold text-[#5e5951] underline decoration-dotted">
                                    Tampilkan Lagi
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
@endsection
