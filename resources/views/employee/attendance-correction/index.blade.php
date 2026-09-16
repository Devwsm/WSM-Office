{{--
    employee/attendance-correction/index.blade.php
    ---------------------------------------------------------------------
    2026-09-16 — ajukan Koreksi Presensi + riwayat pengajuan sendiri.
    Struktur & style SENGAJA disamain persis employee/leave/index.blade.php
    (Fase 5) & employee/overtime/index.blade.php (Fase 7) biar konsisten.
    ---------------------------------------------------------------------
--}}
@extends('layouts.employee', ['title' => 'Koreksi Presensi', 'navActive' => 'pengajuan'])

@section('content')
    <div class="mb-5 flex items-start justify-between gap-3">
        <div>
            <h2 class="text-[30px] font-black leading-none tracking-tight">Koreksi Presensi</h2>
            <p class="mt-1.5 text-xs text-muted">Lupa absen atau salah jam? Ajukan koreksi ke atasan langsung kamu,
                atau Owner kalau kamu gak punya atasan.</p>
        </div>
    </div>

    <div class="mb-5 flex flex-wrap gap-2">
        <a href="{{ route('employee.leave.index') }}" class="btn-wsm-white flex-none py-2! px-3! text-[11px]!">Izin/Cuti
            →</a>
        <a href="{{ route('employee.overtime.index') }}" class="btn-wsm-white flex-none py-2! px-3! text-[11px]!">Lembur →</a>
    </div>

    {{-- Form ajukan --}}
    <div class="card-wsm-white mb-6">
        <p class="mb-3.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Ajukan Baru</p>
        <form method="POST" action="{{ route('employee.attendanceCorrection.store') }}" class="grid gap-3">
            @csrf
            <div>
                <label class="mb-1 block text-[11px] font-extrabold text-muted">Tanggal yang mau dikoreksi</label>
                <input type="date" name="date" max="{{ now()->toDateString() }}" class="input-wsm" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold text-muted">Jam Masuk (seharusnya)</label>
                    <input type="time" name="requested_clock_in" class="input-wsm">
                </div>
                <div>
                    <label class="mb-1 block text-[11px] font-extrabold text-muted">Jam Pulang (seharusnya)</label>
                    <input type="time" name="requested_clock_out" class="input-wsm">
                </div>
            </div>
            <p class="text-[11px] text-muted">Isi minimal salah satu — kosongin yang nggak perlu dikoreksi.</p>
            <div>
                <label class="mb-1 block text-[11px] font-extrabold text-muted">Mode kerja hari itu</label>
                <select name="requested_mode" class="input-wsm">
                    <option value="kantor">Kantor (WFO)</option>
                    <option value="wfh">WFH</option>
                    <option value="lapangan">Lapangan</option>
                    <option value="gigs">Gigs</option>
                </select>
                <p class="mt-1 text-[10px] text-muted">Cuma kepake kalau hari itu kamu belum absen sama sekali —
                    kalau udah ada sesi presensi, mode yang ada gak diubah.</p>
            </div>
            <textarea name="reason" rows="3" placeholder="Alasan koreksi, mis. lupa buka aplikasi, salah tap, dll..."
                class="input-wsm" required></textarea>
            <button type="submit" class="btn-wsm-black">Kirim Pengajuan</button>
        </form>
    </div>

    {{-- Riwayat --}}
    <p class="mb-2.5 text-xs font-extrabold uppercase tracking-wide text-[#5e5952]">Riwayat Pengajuan</p>
    @if ($rows->isEmpty())
        <div class="card-wsm-white text-center">
            <p class="text-xs text-muted">Belum ada pengajuan.</p>
        </div>
    @else
        <div class="grid gap-2.5">
            @foreach ($rows as $row)
                <div class="rounded-wsm border border-line bg-white p-4" x-data="{ showCancel: false }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <strong class="block text-sm">{{ $row->date->translatedFormat('d M Y') }}</strong>
                            <span class="mt-0.5 block text-[11px] text-muted">{{ $row->requestedTimeLabel() }} ·
                                {{ $row->modeLabel() }}</span>
                            <p class="mt-1.5 text-xs text-ink">{{ $row->reason }}</p>

                            @if ($row->status === 'ditolak' && $row->decision_note)
                                <p class="mt-2 rounded-xl bg-[#fff0ee] p-2.5 text-[11px] text-[#a83d35]">
                                    <strong>Alasan ditolak:</strong> {{ $row->decision_note }}
                                </p>
                            @endif
                            @if ($row->status === 'dibatalkan')
                                <p class="mt-2 rounded-xl bg-[#f2f0eb] p-2.5 text-[11px] text-[#5e5952]">
                                    <strong>Dibatalkan oleh {{ $row->canceller?->name ?? '-' }}:</strong>
                                    {{ $row->cancellation_reason }}
                                </p>
                            @endif
                            @if ($row->status === 'disetujui')
                                <p class="mt-1.5 text-[11px] text-muted">Disetujui oleh
                                    {{ $row->approver?->name ?? '-' }} · udah diterapkan ke presensi kamu</p>
                            @endif
                        </div>
                        <span class="{{ $row->statusBadgeClass() }} flex-none">{{ $row->statusLabel() }}</span>
                    </div>

                    @if ($row->isCancellable())
                        <div class="mt-3 border-t border-[#eee8df] pt-3">
                            <button type="button" x-show="!showCancel" @click="showCancel = true"
                                class="text-[11px] font-extrabold text-[#a83d35]">
                                Batalkan pengajuan
                            </button>
                            <form x-show="showCancel" method="POST"
                                action="{{ route('employee.attendanceCorrection.cancel', $row) }}" class="grid gap-2"
                                data-confirm="Pengajuan ini akan dibatalkan dan gak bisa diaktifkan lagi."
                                data-confirm-title="Batalkan pengajuan?" data-confirm-button="Ya, batalkan"
                                data-confirm-danger="1">
                                @csrf
                                <textarea name="cancellation_reason" rows="2" placeholder="Alasan pembatalan..." class="input-wsm text-xs!"
                                    required></textarea>
                                <div class="flex gap-2">
                                    <button type="button" @click="showCancel = false"
                                        class="btn-wsm-white py-2! text-[11px]!">Batal</button>
                                    <button type="submit" class="btn-wsm-red py-2! text-[11px]!">Ya, Batalkan
                                        Pengajuan</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
