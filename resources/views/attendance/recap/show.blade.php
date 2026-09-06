{{--
    attendance/recap/show.blade.php
    ---------------------------------------------------------------------
    Fase 4 — riwayat absensi bulanan 1 karyawan, dilihat dari sisi
    Manajer/Owner/HRD. Ada link ke Google Maps per titik absen (bukan map
    interaktif ulang di sini — cukup link, biar halaman ringan) dan
    thumbnail foto selfie kalau ada.
    ---------------------------------------------------------------------
--}}
@extends('layouts.app', ['title' => 'Riwayat — ' . $employee->name, 'navActive' => 'attendance'])

@section('content')
    <a href="{{ route('attendance.recap.index') }}" class="text-xs font-extrabold text-muted hover:text-ink">← Kembali
        ke Rekap</a>

    <div class="mt-3 mb-6 flex flex-wrap items-end justify-between gap-3.5">
        <div>
            <h2 class="text-[36px] font-black leading-[0.98] tracking-tight">{{ $employee->name }}</h2>
            <p class="mt-1 text-[13px] text-muted">{{ $employee->roleLabel() }} · {{ $employee->division ?? '-' }} ·
                {{ \Carbon\Carbon::createFromFormat('Y-m', $currentMonth)->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex gap-1.5">
            <a href="{{ route('attendance.recap.show', [$employee, 'bulan' => $prevMonth]) }}"
                class="btn-wsm-white py-2.5! px-3.5! text-xs!">← Bulan Lalu</a>
            <a href="{{ route('attendance.recap.show', [$employee, 'bulan' => $nextMonth]) }}"
                class="btn-wsm-white py-2.5! px-3.5! text-xs!">Bulan Depan →</a>
        </div>
    </div>

    @if ($leaves->isNotEmpty())
        <div class="mb-4">
            <p class="mb-2 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Izin/Cuti Bulan Ini</p>
            <div class="grid gap-2">
                @foreach ($leaves as $leave)
                    <div class="rounded-2xl border border-line bg-white p-3.5 flex items-center justify-between gap-3">
                        <div>
                            <strong class="block text-xs">{{ $leave->typeLabel() }}</strong>
                            <span class="text-[11px] text-muted">
                                {{ $leave->start_date->translatedFormat('d M') }} –
                                {{ $leave->end_date->translatedFormat('d M Y') }} ({{ $leave->work_days }} hari
                                kerja)
                            </span>
                        </div>
                        <span class="{{ $leave->statusBadgeClass() }} flex-none">{{ $leave->statusLabel() }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Tidak ada data absensi di bulan ini.</p>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($rows as $row)
                <div class="section-card rounded-3xl border border-line bg-white p-4.5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <strong class="block text-sm">{{ $row->date->translatedFormat('l, d F Y') }}</strong>
                            <span class="mt-0.5 block text-[11px] text-muted">
                                {{ $row->mode === 'wfh' ? 'WFH' : 'Kantor' }}
                                @if ($row->work_context)
                                    · {{ $row->work_context }}
                                @endif
                            </span>
                        </div>
                        <span class="{{ $row->statusBadgeClass($setting) }}">{{ $row->statusLabel($setting) }}</span>
                    </div>

                    <div class="mt-3.5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-paper p-3.5">
                            <span class="text-[10px] font-black uppercase tracking-wide text-muted">Masuk</span>
                            <p class="mt-1 text-sm font-extrabold">{{ $row->clock_in_at?->format('H:i') ?? '—' }}</p>
                            @if ($row->clock_in_lat && $row->clock_in_lng)
                                <a href="https://www.google.com/maps?q={{ $row->clock_in_lat }},{{ $row->clock_in_lng }}"
                                    target="_blank" rel="noopener"
                                    class="mt-1 block text-[10px] font-extrabold text-brand-blue hover:underline">
                                    📍 Lihat lokasi
                                    @if ($row->mode === 'kantor' && $row->clock_in_distance_meters !== null)
                                        ({{ $row->clock_in_distance_meters }}m{{ $row->clock_in_within_radius ? '' : ', luar radius' }})
                                    @endif
                                </a>
                            @endif
                            @if ($row->clock_in_photo)
                                <img src="{{ asset('storage/' . $row->clock_in_photo) }}"
                                    class="mt-2 h-16 w-16 rounded-xl object-cover" alt="Selfie masuk">
                            @endif
                        </div>
                        <div class="rounded-2xl bg-paper p-3.5">
                            <span class="text-[10px] font-black uppercase tracking-wide text-muted">Pulang</span>
                            <p class="mt-1 text-sm font-extrabold">{{ $row->clock_out_at?->format('H:i') ?? '—' }}</p>
                            @if ($row->clock_out_lat && $row->clock_out_lng)
                                <a href="https://www.google.com/maps?q={{ $row->clock_out_lat }},{{ $row->clock_out_lng }}"
                                    target="_blank" rel="noopener"
                                    class="mt-1 block text-[10px] font-extrabold text-brand-blue hover:underline">
                                    📍 Lihat lokasi
                                    @if ($row->mode === 'kantor' && $row->clock_out_distance_meters !== null)
                                        ({{ $row->clock_out_distance_meters }}m{{ $row->clock_out_within_radius ? '' : ', luar radius' }})
                                    @endif
                                </a>
                            @endif
                            @if ($row->clock_out_photo)
                                <img src="{{ asset('storage/' . $row->clock_out_photo) }}"
                                    class="mt-2 h-16 w-16 rounded-xl object-cover" alt="Selfie pulang">
                            @endif
                        </div>
                    </div>

                    @if ($row->wasCorrected())
                        <p class="mt-3 rounded-xl bg-[#eef2ff] p-2.5 text-[10px] text-brand-blue">
                            <strong>Dikoreksi oleh {{ $row->corrector?->name ?? '-' }}</strong>
                            ({{ $row->corrected_at->translatedFormat('d M, H:i') }}): {{ $row->correction_note }}
                        </p>
                    @endif

                    <div class="mt-3 border-t border-[#eee8df] pt-3" x-data="{ showCorrect: false }">
                        <button type="button" x-show="!showCorrect" @click="showCorrect = true"
                            class="text-[11px] font-extrabold text-brand-blue">
                            {{ $row->wasCorrected() ? 'Koreksi lagi' : 'Koreksi jam absen' }}
                        </button>
                        <form x-show="showCorrect" method="POST" action="{{ route('attendance.recap.correct', $row) }}"
                            class="grid gap-2"
                            data-confirm="Jam absen karyawan ini akan diubah dan tercatat siapa yang mengoreksi."
                            data-confirm-title="Koreksi absen ini?" data-confirm-button="Ya, simpan koreksi">
                            @csrf
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="mb-1 block text-[10px] font-extrabold text-muted">Jam
                                        Masuk</label>
                                    <input type="time" name="clock_in_time"
                                        value="{{ $row->clock_in_at?->format('H:i') }}" class="input-wsm text-xs!">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-extrabold text-muted">Jam
                                        Pulang</label>
                                    <input type="time" name="clock_out_time"
                                        value="{{ $row->clock_out_at?->format('H:i') }}" class="input-wsm text-xs!">
                                </div>
                            </div>
                            <textarea name="correction_note" rows="2" placeholder="Alasan koreksi (wajib, karyawan bisa lihat ini)..."
                                class="input-wsm text-xs!" required></textarea>
                            <div class="flex gap-2">
                                <button type="button" @click="showCorrect = false"
                                    class="btn-wsm-white py-2! text-[11px]!">Batal</button>
                                <button type="submit" class="btn-wsm-black py-2! text-[11px]!">Simpan Koreksi</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
